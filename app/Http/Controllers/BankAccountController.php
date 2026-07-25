<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:50',
            'account_holder_name' => 'required|string|max:255',
        ]);

        $request->user()->business->bankAccounts()->create($validated);

        return back()->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function destroy(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        abort_unless($bankAccount->business_id === $request->user()->business_id, 403);

        $bankAccount->delete();

        return back()->with('success', 'Rekening bank berhasil dihapus.');
    }
}
