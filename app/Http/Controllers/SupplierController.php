<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupplierController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Suppliers/Index', [
            'suppliers' => $request->user()->business->suppliers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $request->user()->business->suppliers()->create($validated);

        return back()->with('success', 'Pemasok berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeBusiness($request, $supplier);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
        ]);

        $supplier->update($validated);

        return back()->with('success', 'Pemasok berhasil diperbarui.');
    }

    public function destroy(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorizeBusiness($request, $supplier);

        $supplier->delete();

        return back()->with('success', 'Pemasok berhasil dihapus.');
    }

    private function authorizeBusiness(Request $request, Supplier $supplier): void
    {
        abort_unless($supplier->business_id === $request->user()->business_id, 403);
    }
}
