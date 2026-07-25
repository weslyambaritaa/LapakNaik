<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGateway;
use App\Models\Business;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StorefrontController extends Controller
{
    public function show(Request $request, string $slug, PaymentGateway $gateway): Response
    {
        $business = Business::where('slug', $slug)->firstOrFail();

        $products = $business->products()
            ->where('is_active', true)
            ->with('category:id,name')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get(['id', 'category_id', 'name', 'description', 'image_path', 'price', 'unit', 'stock']);

        return Inertia::render('Storefront/Show', [
            'business' => $business->only(['name', 'slug', 'category', 'description', 'address', 'phone', 'qris_image_url']),
            'products' => $products,
            'categories' => $business->categories()->orderBy('name')->get(['id', 'name']),
            'bankAccounts' => $business->bankAccounts()->orderBy('bank_name')->get(['id', 'bank_name', 'account_number', 'account_holder_name']),
            'filters' => $request->only(['search', 'category_id']),
            'midtrans' => [
                'client_key' => $gateway->clientKey(),
                'is_production' => $gateway->isProduction(),
            ],
        ]);
    }
}
