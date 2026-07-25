<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    /**
     * Handle Midtrans's payment notification callback.
     *
     * Notifications can arrive more than once for the same order (Midtrans
     * retries on non-200 responses); OrderFulfillmentService is idempotent
     * against that, so a repeat notification for an already-finalized order
     * is a safe no-op rather than double-crediting or double-restoring stock.
     */
    public function handle(Request $request, PaymentGateway $gateway, OrderFulfillmentService $fulfillment): JsonResponse
    {
        $payload = $request->all();

        if (! $gateway->verifySignature($payload)) {
            Log::warning('Midtrans webhook signature mismatch', ['order_id' => $payload['order_id'] ?? null]);

            abort(403, 'Invalid signature.');
        }

        $orderId = $payload['order_id'] ?? null;
        $status = $payload['transaction_status'] ?? null;
        $fraudStatus = $payload['fraud_status'] ?? null;

        DB::transaction(function () use ($orderId, $status, $fraudStatus, $fulfillment) {
            $transaction = Transaction::where('invoice_number', $orderId)->lockForUpdate()->first();

            if (! $transaction) {
                return;
            }

            $isPaid = $status === 'settlement' || ($status === 'capture' && $fraudStatus === 'accept');
            $isFailed = in_array($status, ['deny', 'cancel', 'expire'], true);

            if ($isPaid) {
                $fulfillment->markPaid($transaction);
            } elseif ($isFailed) {
                $fulfillment->markExpired($transaction);
            }

            // 'pending', or 'capture' awaiting fraud review ('challenge'), is
            // left untouched — still awaiting a final outcome.
        });

        return response()->json(['message' => 'OK']);
    }
}
