<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Staff-facing screen for online orders paid outside Midtrans (QRIS Toko,
 * Tunai) — methods with no webhook, so a human has to confirm the money
 * actually arrived before stock/status moves out of 'pending'.
 */
class IncomingOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();
        $like = $this->caseInsensitiveLikeOperator();

        $transactions = $request->user()->business->transactions()
            ->where('channel', Transaction::CHANNEL_ONLINE)
            ->where('status', Transaction::STATUS_PENDING)
            ->when($search, fn ($query, $search) => $query->where(function ($inner) use ($search, $like) {
                $inner->where('invoice_number', $like, "%{$search}%")
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery
                        ->where('name', $like, "%{$search}%")
                        ->orWhere('phone', $like, "%{$search}%"));
            }))
            ->with(['items.product:id,name', 'customer:id,name,phone', 'payment'])
            ->oldest()
            ->get();

        return Inertia::render('IncomingOrders/Index', [
            'transactions' => $transactions,
            'filters' => ['search' => $search],
        ]);
    }

    public function confirm(Request $request, Transaction $transaction, OrderFulfillmentService $fulfillment): RedirectResponse
    {
        $this->authorizeBusiness($request, $transaction);

        if ($transaction->status !== Transaction::STATUS_PENDING || $transaction->channel !== Transaction::CHANNEL_ONLINE) {
            return back()->with('error', 'Pesanan ini tidak bisa dikonfirmasi.');
        }

        $transaction->loadMissing('payment');

        // Midtrans orders confirm themselves via webhook — a staff member
        // has no business overriding that here.
        if ($transaction->payment?->method === Payment::METHOD_QRIS) {
            return back()->with('error', 'Pesanan QRIS Midtrans dikonfirmasi otomatis, bukan manual.');
        }

        if ($transaction->payment?->method === Payment::METHOD_CASH) {
            $request->validate([
                'amount_received' => ['required', 'integer', 'min:'.(int) $transaction->total],
            ]);
        }

        $fulfillment->markPaid($transaction);

        return back()->with('success', "Pesanan {$transaction->invoice_number} ditandai lunas.");
    }

    private function authorizeBusiness(Request $request, Transaction $transaction): void
    {
        abort_unless($transaction->business_id === $request->user()->business_id, 403);
    }

    // 'ilike' is Postgres-only (what production actually runs on); the local
    // test suite runs on SQLite, which only understands 'like' — SQLite's
    // LIKE is already case-insensitive for ASCII, so plain 'like' matches
    // the same behavior there.
    private function caseInsensitiveLikeOperator(): string
    {
        return DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
