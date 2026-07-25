<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'business_id', 'category_id', 'sku', 'name', 'description',
    'price', 'cost_price', 'stock', 'unit', 'is_active',
])]
class Product extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'cost_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // Deliberately not mass-assignable — set via ProductController after the
    // uploaded file is stored, so a form can never point image_path at an
    // arbitrary path.
    protected function imageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->image_path ? Storage::disk('public')->url($this->image_path) : null);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    protected function isLowStock(): Attribute
    {
        return Attribute::get(fn () => $this->stock <= 5);
    }
}
