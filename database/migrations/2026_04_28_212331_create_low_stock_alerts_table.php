<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('low_stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->unique()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unsignedInteger('stock');
            $table->unsignedInteger('min_stock');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_detected_at')->nullable()->index();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('low_stock_alerts');
    }
};
