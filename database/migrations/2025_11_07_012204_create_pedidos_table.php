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
        Schema::create('pedidos', function (Blueprint $table) {
            // ID único del pedido (Llave primaria)
            $table->id();
            
            // ID de la mesa asociada a este pedido (Relacionado con Mesas.id)
            $table->foreignId('mesa_id')->constrained('mesas')->onDelete('restrict');
            
            // Número de comensales en la mesa (lo pides en tu modal)
            $table->integer('comensales')->default(1);
            
            // Estado actual del pedido
            // 'A' = Abierto (el cliente está consumiendo)
            // 'C' = Cerrado (el cliente ya pagó)
            // 'X' = Cancelado (se anuló el pedido)
            $table->enum('estado', ['A', 'C', 'X'])->default('A');
            
            // Fecha y hora de apertura (se registra al crear el pedido)
            $table->datetime('fecha_apertura')->useCurrent();
            
            // Fecha y hora de cierre (se actualiza al cobrar)
            $table->datetime('fecha_cierre')->nullable();
            
            // Monto total del pedido (se calcula al cerrar/cobrar)
            $table->decimal('total', 10, 2)->default(0.00);
            
            // Índices para performance
            $table->index(['mesa_id', 'estado']);
            $table->index('fecha_apertura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
