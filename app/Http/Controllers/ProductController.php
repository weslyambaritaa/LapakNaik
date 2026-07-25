<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $business = $request->user()->business;

        $products = $business->products()
            ->with('category')
            ->when($request->search, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->when($request->category_id, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $business->categories()->orderBy('name')->get(['id', 'name']),
            'suppliers' => $business->suppliers()->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'category_id']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $request->user()->business_id;

        $validated = $this->validateProduct($request, $businessId);

        $product = $request->user()->business->products()->make($validated);
        $product->image_path = $this->storeImage($request);
        $product->save();

        if ($product->stock > 0) {
            $this->recordMovement($product, StockMovement::TYPE_IN, $product->stock, $request, 'Stok awal');
        }

        return back()->with('success', 'Produk berhasil ditambahkan.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeBusiness($request, $product);

        $validated = $this->validateProduct($request, $product->business_id, $product->id);

        $product->fill($validated);

        if ($request->hasFile('image')) {
            $this->deleteImage($product);
            $product->image_path = $this->storeImage($request);
        }

        $product->save();

        return back()->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeBusiness($request, $product);

        $this->deleteImage($product);
        $product->delete();

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    public function adjustStock(Request $request, Product $product): RedirectResponse
    {
        $this->authorizeBusiness($request, $product);

        $validated = $request->validate([
            'type' => ['required', Rule::in([StockMovement::TYPE_IN, StockMovement::TYPE_OUT, StockMovement::TYPE_ADJUSTMENT])],
            'quantity' => 'required|integer|min:1',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($product, $validated, $request) {
            $delta = $validated['type'] === StockMovement::TYPE_OUT
                ? -$validated['quantity']
                : $validated['quantity'];

            if ($product->stock + $delta < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok tidak mencukupi.',
                ]);
            }

            $product->increment('stock', $delta);

            StockMovement::create([
                'product_id' => $product->id,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'quantity' => $delta,
                'note' => $validated['note'] ?? null,
            ]);
        });

        return back()->with('success', 'Stok berhasil diperbarui.');
    }

    private function validateProduct(Request $request, int $businessId, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('business_id', $businessId),
            ],
            'sku' => [
                'nullable', 'string', 'max:255',
                Rule::unique('products')->where('business_id', $businessId)->ignore($ignoreId),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|integer|min:0',
            'cost_price' => 'nullable|integer|min:0',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string|max:50',
            'is_active' => 'sometimes|boolean',
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        unset($validated['image']);

        return $validated;
    }

    private function storeImage(Request $request): ?string
    {
        return $request->hasFile('image')
            ? $request->file('image')->store('products', 'public')
            : null;
    }

    private function deleteImage(Product $product): void
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
    }

    private function recordMovement(Product $product, string $type, int $quantity, Request $request, ?string $note = null): void
    {
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $request->user()->id,
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note,
        ]);
    }

    private function authorizeBusiness(Request $request, Product $product): void
    {
        abort_unless($product->business_id === $request->user()->business_id, 403);
    }
}
