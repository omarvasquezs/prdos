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
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->integer('related_comprobante_id')->nullable()->after('cod_comprobante');
            $table->integer('tipo_nota_credito')->nullable()->default(1)->after('related_comprobante_id'); // 1: Anulación de la operación
            $table->text('sustento')->nullable()->after('tipo_nota_credito');
            
            $table->foreign('related_comprobante_id')->references('id')->on('comprobantes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropForeign(['related_comprobante_id']);
            $table->dropColumn(['related_comprobante_id', 'tipo_nota_credito', 'sustento']);
        });
    }
};
