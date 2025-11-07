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
        Schema::create('pedido_items', function (Blueprint $table) {
            // ID único del item (Llave primaria)
            $table->id();
            
            // ID del pedido al que pertenece este item (Relacionado con Pedidos.id)
            $table->foreignId('pedido_id')->constrained('pedidos')->onDelete('cascade');
            
            // ID del producto vendido (Relacionado con products.id)
            // Se usa bigint(20) unsigned para coincidir con tu tabla 'products'
            $table->foreignId('producto_id')->constrained('products')->onDelete('restrict');
            
            // Cantidad de este producto (ej: 2 pollos, 3 gaseosas)
            $table->integer('cantidad')->default(1);
            
            // Precio del producto AL MOMENTO de la venta
            // Se usa decimal(8,2) para coincidir con tu tabla 'products'
            // Es importante guardarlo aquí para registros históricos.
            $table->decimal('precio_unitario', 8, 2);
            
            // Índices para performance
            $table->index(['pedido_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
