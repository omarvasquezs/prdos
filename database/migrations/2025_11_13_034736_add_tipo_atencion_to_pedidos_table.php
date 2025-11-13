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
            // Add tipo_atencion: P=Presencial, D=Delivery, R=Recojo
            $table->enum('tipo_atencion', ['P', 'D', 'R'])->default('P')->after('id');
            
            // Make mesa_id nullable since delivery/recojo don't need table
            $table->unsignedBigInteger('mesa_id')->nullable()->change();
            
            // Add fields for delivery and recojo
            $table->string('cliente_nombre', 255)->nullable()->after('comensales');
            $table->string('cliente_telefono', 20)->nullable()->after('cliente_nombre');
            $table->text('direccion_entrega')->nullable()->after('cliente_telefono');
            $table->text('notas')->nullable()->after('direccion_entrega');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn(['tipo_atencion', 'cliente_nombre', 'cliente_telefono', 'direccion_entrega', 'notas']);
            
            // Revert mesa_id to NOT NULL
            $table->unsignedBigInteger('mesa_id')->nullable(false)->change();
        });
    }
};
