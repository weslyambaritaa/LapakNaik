<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['business_id', 'bank_name', 'account_number', 'account_holder_name'])]
class BankAccount extends Model
{
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
