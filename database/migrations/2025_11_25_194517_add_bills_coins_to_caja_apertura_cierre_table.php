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
        Schema::table('caja_apertura_cierre', function (Blueprint $table) {
            $table->decimal('monto_apertura_billetes', 10, 2)->nullable()->after('monto_apertura');
            $table->decimal('monto_apertura_monedas', 10, 2)->nullable()->after('monto_apertura_billetes');
            $table->decimal('monto_cierre_billetes', 10, 2)->nullable()->after('monto_cierre');
            $table->decimal('monto_cierre_monedas', 10, 2)->nullable()->after('monto_cierre_billetes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja_apertura_cierre', function (Blueprint $table) {
            $table->dropColumn([
                'monto_apertura_billetes',
                'monto_apertura_monedas',
                'monto_cierre_billetes',
                'monto_cierre_monedas'
            ]);
        });
    }
};
