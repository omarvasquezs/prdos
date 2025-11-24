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
        Schema::table('pedidos', function (Blueprint $table) {
            $table->enum('estado_entrega', ['P', 'L', 'E'])
                ->nullable()
                ->comment("Estado de entrega (solo para Delivery/Recojo): 'P' = En Preparación, 'L' = Listo, 'E' = Entregado")
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            //
        });
    }
};
