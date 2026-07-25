<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function index(Request $request): Response
    {
        $customers = $request->user()->business->customers()
            ->withCount('transactions')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers')->where('business_id', $businessId),
            ],
            'email' => 'nullable|email|max:255',
        ]);

        $request->user()->business->customers()->create($validated);

        return back()->with('success', 'Pelanggan berhasil ditambahkan.');
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeBusiness($request, $customer);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => [
                'nullable', 'string', 'max:50',
                Rule::unique('customers')->where('business_id', $customer->business_id)->ignore($customer->id),
            ],
            'email' => 'nullable|email|max:255',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Pelanggan berhasil diperbarui.');
    }

    public function destroy(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorizeBusiness($request, $customer);

        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }

    private function authorizeBusiness(Request $request, Customer $customer): void
    {
        abort_unless($customer->business_id === $request->user()->business_id, 403);
    }
}
