<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Business;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontOrderController extends Controller
{
    public function store(Request $request, string $slug, PaymentGateway $gateway, OrderFulfillmentService $fulfillment): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'payment_method' => ['required', Rule::in([
                Payment::METHOD_QRIS, Payment::METHOD_QRIS_STORE, Payment::METHOD_CASH, Payment::METHOD_TRANSFER,
            ])],
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('business_id', $business->id),
            ],
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validated['payment_method'] === Payment::METHOD_QRIS_STORE && ! $business->qris_image_path) {
            throw ValidationException::withMessages([
                'payment_method' => 'Toko ini belum mengaktifkan QRIS sendiri.',
            ]);
        }

        if ($validated['payment_method'] === Payment::METHOD_TRANSFER && $business->bankAccounts()->doesntExist()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Toko ini belum menambahkan rekening bank.',
            ]);
        }

        $transaction = DB::transaction(function () use ($validated, $business) {
            $productIds = collect($validated['items'])->pluck('product_id');

            $products = Product::whereIn('id', $productIds)
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($validated['items'] as $item) {
                $product = $products->get($item['product_id']);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'items' => 'Salah satu produk sudah tidak tersedia.',
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$product->name} tidak mencukupi.",
                    ]);
                }

                $lineSubtotal = $product->price * $item['quantity'];
                $subtotal += $lineSubtotal;

                $lines[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'subtotal' => $lineSubtotal,
                ];
            }

            // Phone is optional here — an anonymous order (no phone) isn't
            // attached to any customer at all, so it earns no loyalty points
            // and never shows up in the Pelanggan list. A phone is what
            // turns "just a name for this order" into a tracked identity.
            $customer = ! empty($validated['customer_phone'])
                ? Customer::firstOrCreate(
                    ['business_id' => $business->id, 'phone' => $validated['customer_phone']],
                    ['name' => $validated['customer_name']]
                )
                : null;

            $transaction = $business->transactions()->create([
                'customer_id' => $customer?->id,
                'user_id' => null,
                'invoice_number' => Transaction::nextInvoiceNumber($business->id),
                'subtotal' => $subtotal,
                'discount' => 0,
                'total' => $subtotal,
                'status' => Transaction::STATUS_PENDING,
                'channel' => Transaction::CHANNEL_ONLINE,
            ]);

            foreach ($lines as $line) {
                $transaction->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'subtotal' => $line['subtotal'],
                ]);

                $line['product']->decrement('stock', $line['quantity']);

                StockMovement::create([
                    'product_id' => $line['product']->id,
                    'type' => StockMovement::TYPE_OUT,
                    'quantity' => -$line['quantity'],
                    'reference' => $transaction->invoice_number,
                    'note' => 'Pesanan online (menunggu pembayaran)',
                ]);
            }

            $transaction->payment()->create([
                'method' => $validated['payment_method'],
                'amount' => $transaction->total,
                'status' => Payment::STATUS_PENDING,
                'gateway_reference' => $transaction->invoice_number,
            ]);

            return $transaction;
        });

        $orderStatusUrl = route('storefront.order', ['slug' => $business->slug, 'transaction' => $transaction->id]);

        // QRIS Toko and Tunai never touch Midtrans at all — there's no
        // gateway session to create, and no webhook will ever arrive for
        // them. They stay 'pending' until a staff member confirms payment
        // was actually received, from the Pesanan Masuk screen.
        if ($validated['payment_method'] !== Payment::METHOD_QRIS) {
            return response()->json([
                'transaction_id' => $transaction->id,
                'order_status_url' => $orderStatusUrl,
            ]);
        }

        try {
            $snapToken = $gateway->createSnapToken($transaction);
        } catch (\Throwable $e) {
            // The order was already committed (and its stock reserved) before
            // this call — if the gateway can't issue a payment session, the
            // customer would otherwise be stuck with no way to pay. Release
            // the reservation immediately instead of waiting on the 60-minute
            // expiry sweep.
            Log::error('Midtrans snap token request failed', ['invoice' => $transaction->invoice_number, 'error' => $e->getMessage()]);

            $fulfillment->markExpired($transaction);

            return response()->json([
                'message' => 'Gagal membuat sesi pembayaran. Silakan coba lagi beberapa saat.',
            ], 503);
        }

        return response()->json([
            'transaction_id' => $transaction->id,
            'snap_token' => $snapToken,
            'is_production' => $gateway->isProduction(),
            'order_status_url' => $orderStatusUrl,
        ]);
    }

    /**
     * Look up a returning customer by phone for the checkout modal — like a
     * minimarket membership number. Deliberately returns only the name
     * (never the full customer list, which would leak every customer's
     * phone number to any anonymous storefront visitor) and is rate-limited
     * separately from checkout to blunt phone-number enumeration.
     */
    public function lookupCustomer(Request $request, string $slug): JsonResponse
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'phone' => 'required|string|max:50',
        ]);

        $customer = Customer::where('business_id', $business->id)
            ->where('phone', $validated['phone'])
            ->first(['name']);

        return response()->json(['name' => $customer?->name]);
    }

    public function show(string $slug, Transaction $transaction): Response
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        abort_unless($transaction->business_id === $business->id, 404);

        $transaction->load(['items.product', 'payment']);

        return Inertia::render('Storefront/OrderStatus', [
            'business' => $business->only(['name', 'slug', 'qris_image_url']),
            'bankAccounts' => $business->bankAccounts()->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
            'transaction' => $transaction,
        ]);
    }

}
