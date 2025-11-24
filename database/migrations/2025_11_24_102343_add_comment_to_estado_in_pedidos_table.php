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
            $table->enum('estado', ['A', 'C', 'X'])
                ->default('A')
                ->comment("Estado actual del pedido: 'A' = Abierto (el cliente está consumiendo), 'C' = Cerrado (el cliente ya pagó), 'X' = Cancelado (se anuló el pedido)")
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
