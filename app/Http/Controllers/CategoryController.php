<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = $request->user()->business->categories()
            ->withCount('products')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('business_id', $businessId),
            ],
        ]);

        $request->user()->business->categories()->create($validated);

        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeBusiness($request, $category);

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('categories')->where('business_id', $category->business_id)->ignore($category->id),
            ],
        ]);

        $category->update($validated);

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->authorizeBusiness($request, $category);

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    private function authorizeBusiness(Request $request, Category $category): void
    {
        abort_unless($category->business_id === $request->user()->business_id, 403);
    }
}
