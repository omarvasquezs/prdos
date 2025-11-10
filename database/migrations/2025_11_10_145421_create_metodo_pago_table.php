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
        Schema::create('metodo_pago', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('nom_metodo_pago', 255);
            $table->boolean('habilitado');
        });
        
        // Insertar métodos de pago iniciales
        DB::table('metodo_pago')->insert([
            ['nom_metodo_pago' => 'YAPE / PLIN', 'habilitado' => true],
            ['nom_metodo_pago' => 'PINPAD / POS', 'habilitado' => false],
            ['nom_metodo_pago' => 'EFECTIVO', 'habilitado' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metodo_pago');
    }
};
