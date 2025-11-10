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
        Schema::create('comprobante_counter', function (Blueprint $table) {
            $table->char('tipo_comprobante', 1)->primary();
            $table->integer('last_value');
        });
        
        // Insertar valores iniciales
        DB::table('comprobante_counter')->insert([
            ['tipo_comprobante' => 'B', 'last_value' => 0],
            ['tipo_comprobante' => 'F', 'last_value' => 0],
            ['tipo_comprobante' => 'N', 'last_value' => 0],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobante_counter');
    }
};
