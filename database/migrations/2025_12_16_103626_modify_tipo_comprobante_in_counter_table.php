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
        Schema::table('comprobante_counter', function (Blueprint $table) {
            $table->string('tipo_comprobante', 4)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobante_counter', function (Blueprint $table) {
            // We cannot easily revert to char(1) if there are longer values, 
            // but for rollback purposes we can try.
            // In a real scenario we might need data migration.
            $table->char('tipo_comprobante', 1)->change();
        });
    }
};
