<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\OrderFulfillmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('payment:simulate {invoice} {--fail : Mark it as a failed/expired payment instead of a successful one}')]
#[Description('Dev-only: mark a pending online order as paid (or failed), standing in for a Midtrans sandbox webhook the local server can never actually receive')]
class SimulatePayment extends Command
{
    public function handle(OrderFulfillmentService $fulfillment): int
    {
        if (app()->isProduction()) {
            $this->error('This command is disabled in production — real orders are only ever confirmed by the real Midtrans webhook.');

            return self::FAILURE;
        }

        $invoice = (string) $this->argument('invoice');

        $transaction = Transaction::where('invoice_number', $invoice)->first();

        if (! $transaction) {
            $this->error("No order found with invoice number {$invoice}.");

            return self::FAILURE;
        }

        if ($transaction->status !== Transaction::STATUS_PENDING) {
            $this->error("Order {$invoice} is already '{$transaction->status}', nothing to simulate.");

            return self::FAILURE;
        }

        if ($this->option('fail')) {
            $fulfillment->markExpired($transaction);
            $this->info("Order {$invoice} marked as expired — stock restored.");
        } else {
            $fulfillment->markPaid($transaction);
            $this->info("Order {$invoice} marked as paid.");
        }

        return self::SUCCESS;
    }
}
