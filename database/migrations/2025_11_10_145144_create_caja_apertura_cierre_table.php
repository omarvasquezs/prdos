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
        Schema::create('caja_apertura_cierre', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('datetime_apertura');
            $table->decimal('monto_apertura', 10, 2);
            $table->integer('id_usuario_apertura');
            $table->dateTime('datetime_cierre')->nullable();
            $table->decimal('monto_cierre', 10, 2)->nullable();
            $table->integer('id_usuario_cierre')->nullable();
            
            // Índices para mejor rendimiento
            $table->index('datetime_apertura');
            $table->index('id_usuario_apertura');
            $table->index('id_usuario_cierre');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_apertura_cierre');
    }
};
