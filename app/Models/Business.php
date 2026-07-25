<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['owner_id', 'name', 'slug', 'category', 'description', 'address', 'phone', 'logo_path'])]
class Business extends Model
{
    use HasFactory;

    protected $appends = ['qris_image_url'];

    // Deliberately not mass-assignable — set via BusinessSettingsController
    // after the uploaded file is stored, same pattern as Product::image_path.
    protected function qrisImageUrl(): Attribute
    {
        return Attribute::get(fn () => $this->qris_image_path ? Storage::disk('public')->url($this->qris_image_path) : null);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function cashFlows(): HasMany
    {
        return $this->hasMany(CashFlow::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(BusinessScore::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }
}
