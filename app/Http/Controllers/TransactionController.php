<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * The cashier (POS) screen: product picker + cart.
     */
    public function create(Request $request): Response
    {
        $business = $request->user()->business;

        return Inertia::render('Pos/Index', [
            'products' => $business->products()
                ->where('is_active', true)
                ->with('category:id,name')
                ->orderBy('name')
                ->get(['id', 'category_id', 'name', 'price', 'stock', 'unit']),
            'customers' => $business->customers()->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    /**
     * Transaction history.
     */
    public function index(Request $request): Response
    {
        $transactions = $request->user()->business->transactions()
            ->with(['customer:id,name', 'cashier:id,name'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Pos/History', [
            'transactions' => $transactions,
        ]);
    }

    public function show(Request $request, Transaction $transaction): Response
    {
        $this->authorizeBusiness($request, $transaction);

        $transaction->load(['items.product', 'customer', 'cashier', 'payment']);

        return Inertia::render('Pos/Receipt', [
            'transaction' => $transaction,
            'business' => $request->user()->business,
        ]);
    }

    public function receiptPdf(Request $request, Transaction $transaction): HttpResponse
    {
        $this->authorizeBusiness($request, $transaction);

        $transaction->load(['items.product', 'customer', 'cashier', 'payment']);

        $pdf = Pdf::loadView('pdf.receipt', [
            'transaction' => $transaction,
            'business' => $request->user()->business,
        ])->setPaper([0, 0, 226, 600]); // ~80mm thermal receipt width

        return $pdf->stream("{$transaction->invoice_number}.pdf");
    }

    public function store(Request $request): RedirectResponse
    {
        $business = $request->user()->business;

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => [
                'required',
                Rule::exists('products', 'id')->where('business_id', $business->id),
            ],
            'items.*.quantity' => 'required|integer|min:1',
            'customer_phone' => 'nullable|string|max:50',
            'discount' => 'nullable|integer|min:0',
            'payment_method' => ['required', Rule::in([
                Payment::METHOD_CASH, Payment::METHOD_QRIS, Payment::METHOD_TRANSFER,
            ])],
        ]);

        $transaction = DB::transaction(function () use ($validated, $business, $request) {
            $productIds = collect($validated['items'])->pluck('product_id');

            $products = Product::whereIn('id', $productIds)
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            $lines = [];

            foreach ($validated['items'] as $item) {
                $product = $products[$item['product_id']];

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

            $discount = min($validated['discount'] ?? 0, $subtotal);
            $total = $subtotal - $discount;

            // Phone is the lookup key, like a minimarket membership number —
            // deliberately a lookup only, not an auto-create. A phone that
            // doesn't match any existing customer just means this sale isn't
            // attached to anyone; registering a new customer is a distinct
            // action on the Pelanggan page, not a side effect of checkout.
            $customer = ! empty($validated['customer_phone'])
                ? Customer::where('business_id', $business->id)
                    ->where('phone', trim($validated['customer_phone']))
                    ->first()
                : null;

            $transaction = $business->transactions()->create([
                'customer_id' => $customer?->id,
                'user_id' => $request->user()->id,
                'invoice_number' => Transaction::nextInvoiceNumber($business->id),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => Transaction::STATUS_COMPLETED,
                'channel' => Transaction::CHANNEL_POS,
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
                    'user_id' => $request->user()->id,
                    'type' => StockMovement::TYPE_OUT,
                    'quantity' => -$line['quantity'],
                    'reference' => $transaction->invoice_number,
                ]);
            }

            $transaction->payment()->create([
                'method' => $validated['payment_method'],
                'amount' => $total,
                'status' => Payment::STATUS_PAID,
            ]);

            if ($transaction->customer_id) {
                $transaction->customer->awardLoyaltyPoints($total);
            }

            return $transaction;
        });

        return redirect()->route('pos.receipt', $transaction)
            ->with('success', 'Transaksi berhasil disimpan.');
    }

    /**
     * Cancel a completed sale: restock every item and mark the sale + its
     * payment as refunded. Restricted to owner/admin — a cashier shouldn't
     * be able to unilaterally void their own sales without oversight.
     */
    public function refund(Request $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeBusiness($request, $transaction);

        if ($transaction->status !== Transaction::STATUS_COMPLETED) {
            return back()->with('error', 'Hanya transaksi yang sudah selesai yang bisa direfund.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($transaction, $validated) {
            $transaction->load('items.product');

            foreach ($transaction->items as $item) {
                $item->product->increment('stock', $item->quantity);

                StockMovement::create([
                    'product_id' => $item->product_id,
                    'type' => StockMovement::TYPE_IN,
                    'quantity' => $item->quantity,
                    'reference' => $transaction->invoice_number,
                    'note' => "Refund: {$validated['reason']}",
                ]);
            }

            $transaction->update(['status' => Transaction::STATUS_REFUNDED]);
            $transaction->payment?->update(['status' => Payment::STATUS_REFUNDED]);
        });

        return back()->with('success', 'Transaksi berhasil di-refund, stok sudah dikembalikan.');
    }

    private function authorizeBusiness(Request $request, Transaction $transaction): void
    {
        abort_unless($transaction->business_id === $request->user()->business_id, 403);
    }
}
