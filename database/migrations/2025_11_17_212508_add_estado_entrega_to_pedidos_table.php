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
        if (Schema::hasColumn('pedidos', 'estado_entrega')) {
            return;
        }

        Schema::table('pedidos', function (Blueprint $table) {
            // Estado de entrega para delivery/recojo
            // 'P' = En Preparación
            // 'L' = Listo (para entregar/recoger)
            // 'E' = Entregado/Recogido
            // NULL = No aplica (pedidos de mesa)
            $table->enum('estado_entrega', ['P', 'L', 'E'])->nullable()->after('estado');
            
            // Indicador de si ya fue pagado (independiente de la entrega)
            $table->boolean('pagado')->default(false)->after('estado_entrega');
            
            // Método de pago usado (si ya pagó) - sin foreign key por compatibilidad
            $table->integer('metodo_pago_id')->nullable()->after('pagado');
            
            // Índice para consultas rápidas
            $table->index(['tipo_atencion', 'estado_entrega', 'pagado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropIndex(['tipo_atencion', 'estado_entrega', 'pagado']);
            $table->dropColumn(['estado_entrega', 'pagado', 'metodo_pago_id']);
        });
    }
};
