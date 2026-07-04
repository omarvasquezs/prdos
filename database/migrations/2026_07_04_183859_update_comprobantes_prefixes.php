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
        DB::statement("UPDATE comprobantes SET cod_comprobante = REPLACE(cod_comprobante, 'B001-', 'BBB1-') WHERE tipo_comprobante = 'B'");
        DB::statement("UPDATE comprobantes SET cod_comprobante = REPLACE(cod_comprobante, 'F001-', 'FFF1-') WHERE tipo_comprobante = 'F'");
        DB::statement("UPDATE comprobantes SET observaciones = REPLACE(observaciones, 'B001-', 'BBB1-') WHERE observaciones LIKE '%B001-%'");
        DB::statement("UPDATE comprobantes SET observaciones = REPLACE(observaciones, 'F001-', 'FFF1-') WHERE observaciones LIKE '%F001-%'");
        DB::statement("UPDATE reporte_ingresos SET cod_comprobante = REPLACE(cod_comprobante, 'B001-', 'BBB1-') WHERE cod_comprobante LIKE 'B001-%'");
        DB::statement("UPDATE reporte_ingresos SET cod_comprobante = REPLACE(cod_comprobante, 'F001-', 'FFF1-') WHERE cod_comprobante LIKE 'F001-%'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE comprobantes SET cod_comprobante = REPLACE(cod_comprobante, 'BBB1-', 'B001-') WHERE tipo_comprobante = 'B'");
        DB::statement("UPDATE comprobantes SET cod_comprobante = REPLACE(cod_comprobante, 'FFF1-', 'F001-') WHERE tipo_comprobante = 'F'");
        DB::statement("UPDATE comprobantes SET observaciones = REPLACE(observaciones, 'BBB1-', 'B001-') WHERE observaciones LIKE '%BBB1-%'");
        DB::statement("UPDATE comprobantes SET observaciones = REPLACE(observaciones, 'FFF1-', 'F001-') WHERE observaciones LIKE '%FFF1-%'");
        DB::statement("UPDATE reporte_ingresos SET cod_comprobante = REPLACE(cod_comprobante, 'BBB1-', 'B001-') WHERE cod_comprobante LIKE 'BBB1-%'");
        DB::statement("UPDATE reporte_ingresos SET cod_comprobante = REPLACE(cod_comprobante, 'FFF1-', 'F001-') WHERE cod_comprobante LIKE 'FFF1-%'");
    }
};
