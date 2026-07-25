<?php

namespace App\Console\Commands;

use App\Contracts\PaymentGateway;
use App\Models\Transaction;
use App\Services\OrderFulfillmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:expire-stale-pending-orders')]
#[Description('Release stock reserved by online orders Midtrans never confirmed (webhook missed or never sent)')]
class ExpireStalePendingOrders extends Command
{
    public function handle(OrderFulfillmentService $fulfillment): int
    {
        $cutoff = now()->subMinutes(PaymentGateway::ORDER_EXPIRY_MINUTES);

        $staleIds = Transaction::where('status', Transaction::STATUS_PENDING)
            ->where('channel', Transaction::CHANNEL_ONLINE)
            ->where('created_at', '<', $cutoff)
            ->pluck('id');

        foreach ($staleIds as $id) {
            DB::transaction(function () use ($id, $fulfillment) {
                $transaction = Transaction::whereKey($id)->lockForUpdate()->first();

                if ($transaction) {
                    $fulfillment->markExpired($transaction);
                }
            });
        }

        $this->info("Expired {$staleIds->count()} stale pending order(s).");

        return self::SUCCESS;
    }
}
