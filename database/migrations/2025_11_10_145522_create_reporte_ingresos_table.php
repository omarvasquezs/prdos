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
        Schema::create('reporte_ingresos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cod_comprobante', 100)->nullable();
            $table->integer('metodo_pago_id')->nullable();
            $table->dateTime('fecha')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->float('costo_total')->nullable();
            
            // Índices para mejor rendimiento
            $table->index('fecha');
            $table->index('metodo_pago_id');
            $table->index('cod_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reporte_ingresos');
    }
};
