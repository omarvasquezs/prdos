<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('comprobante_pagos', function (Blueprint $table) {
            $table->id();
            $table->integer('comprobante_id');
            $table->integer('metodo_pago_id');
            $table->decimal('monto', 10, 2);
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('vuelto', 10, 2)->nullable();
            $table->timestamps();

            $table->foreign('comprobante_id')->references('id')->on('comprobantes')->cascadeOnDelete();
            $table->foreign('metodo_pago_id')->references('id')->on('metodo_pago');
            $table->index('comprobante_id');
            $table->index('metodo_pago_id');
        });

        // Backfill existing records
        DB::statement("
            INSERT INTO comprobante_pagos (comprobante_id, metodo_pago_id, monto, created_at, updated_at)
            SELECT id, metodo_pago_id, costo_total, COALESCE(fecha, NOW()), COALESCE(fecha_actualizacion, fecha, NOW())
            FROM comprobantes
            WHERE metodo_pago_id IS NOT NULL AND costo_total > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_pagos');
    }
};
