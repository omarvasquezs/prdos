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
        Schema::create('mesas', function (Blueprint $table) {
            // ID único de la mesa (Llave primaria)
            $table->id();
            
            // Número de mesa visible para el usuario (ej: 5, 10, 12)
            // Es UNIQUE para evitar tener dos "Mesa 5"
            $table->integer('num_mesa')->unique();
            
            // Estado actual de la mesa
            // 'D' = Disponible (valor por defecto)
            // 'O' = Ocupado
            $table->enum('estado', ['D', 'O'])->default('D');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesas');
    }
};
