<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BusinessSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        $business = $request->user()->business;

        return Inertia::render('Settings/Payment', [
            'business' => $business->only(['id', 'name', 'qris_image_url']),
            'bankAccounts' => $business->bankAccounts()->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $business = $request->user()->business;

        $request->validate([
            'qris_image' => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('qris_image')) {
            if ($business->qris_image_path) {
                Storage::disk('public')->delete($business->qris_image_path);
            }

            $business->qris_image_path = $request->file('qris_image')->store('qris', 'public');
            $business->save();
        }

        return back()->with('success', 'QRIS toko berhasil diperbarui.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $business = $request->user()->business;

        if ($business->qris_image_path) {
            Storage::disk('public')->delete($business->qris_image_path);
            $business->qris_image_path = null;
            $business->save();
        }

        return back()->with('success', 'QRIS toko berhasil dihapus.');
    }
}
