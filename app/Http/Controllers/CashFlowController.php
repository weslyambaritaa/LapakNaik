<?php

namespace App\Http\Controllers;

use App\Models\CashFlow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CashFlowController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->business;

        $cashFlows = $business->cashFlows()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        $month = now()->format('Y-m');

        return Inertia::render('CashFlows/Index', [
            'cashFlows' => $cashFlows,
            'summary' => [
                'income' => (int) $business->cashFlows()->where('type', CashFlow::TYPE_IN)->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month])->sum('amount'),
                'expense' => (int) $business->cashFlows()->where('type', CashFlow::TYPE_OUT)->whereRaw("to_char(date, 'YYYY-MM') = ?", [$month])->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in([CashFlow::TYPE_IN, CashFlow::TYPE_OUT])],
            'category' => 'required|string|max:255',
            'amount' => 'required|integer|min:1',
            'description' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $request->user()->business->cashFlows()->create($validated);

        return back()->with('success', 'Catatan kas berhasil ditambahkan.');
    }

    public function destroy(Request $request, CashFlow $cashFlow): RedirectResponse
    {
        abort_unless($cashFlow->business_id === $request->user()->business_id, 403);

        $cashFlow->delete();

        return back()->with('success', 'Catatan kas berhasil dihapus.');
    }
}
