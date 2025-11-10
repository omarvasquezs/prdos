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
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->integer('id', true);
            $table->char('tipo_comprobante', 1);
            $table->integer('user_id')->nullable();
            $table->dateTime('fecha')->nullable();
            $table->dateTime('fecha_actualizacion')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->integer('metodo_pago_id')->nullable();
            $table->bigInteger('num_ruc')->nullable();
            $table->string('razon_social', 256)->nullable();
            $table->text('observaciones')->nullable();
            $table->integer('last_updated_by')->nullable();
            $table->string('cod_comprobante', 32)->nullable();
            $table->float('costo_total')->nullable();
            
            // Índices para mejor rendimiento
            $table->index('user_id');
            $table->index('metodo_pago_id');
            $table->index('last_updated_by');
            $table->index('fecha');
            $table->index('tipo_comprobante');
            $table->index('cod_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes');
    }
};
