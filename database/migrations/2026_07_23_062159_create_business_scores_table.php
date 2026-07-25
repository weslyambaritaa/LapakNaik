<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('period'); // e.g. 2026-09
            $table->unsignedTinyInteger('score'); // 0-100
            $table->decimal('revenue_growth', 8, 2)->nullable();
            $table->decimal('avg_transaction_value', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_scores');
    }
};
