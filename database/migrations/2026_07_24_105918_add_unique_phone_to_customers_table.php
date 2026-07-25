<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phone becomes the natural lookup key for a customer within a business
     * (like a minimarket membership number) — id remains the actual foreign
     * key target, but this stops two records sharing a phone number within
     * the same business. Postgres treats every NULL as distinct, so this
     * doesn't block multiple walk-in customers with no phone recorded.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->unique(['business_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['business_id', 'phone']);
        });
    }
};
