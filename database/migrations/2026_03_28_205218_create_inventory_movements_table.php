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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->string('movement_type', 20)->index(); //in, out, adjustment
            $table->string('reason', 30)->index(); //purchase, sale, return, correction, etc.
            $table->unsignedBigInteger('quantity'); //signed:+entrada, -salida

            $table->unsignedBigInteger('stock_before'); //stock antes del movimiento
            $table->unsignedBigInteger('stock_after'); //stock resultante después del movimiento

            $table->nullableMorphs('reference'); //Referencia polimórfica a la entidad que generó el movimiento (sale, purchase, etc.)
            $table->dateTime('occurred_at')->index(); //Fecha y hora del movimiento

            $table->text('notes')->nullable(); //Notas adicionales sobre el movimiento
            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
