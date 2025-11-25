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
            $table->boolean('sunat_success')->default(false)->after('costo_total');
            $table->text('sunat_error')->nullable()->after('sunat_success');
            $table->string('enlace_pdf')->nullable()->after('sunat_error');
            $table->string('enlace_xml')->nullable()->after('enlace_pdf');
            $table->string('enlace_cdr')->nullable()->after('enlace_xml');
            $table->string('sunat_description')->nullable()->after('enlace_cdr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes', function (Blueprint $table) {
            $table->dropColumn([
                'sunat_success',
                'sunat_error',
                'enlace_pdf',
                'enlace_xml',
                'enlace_cdr',
                'sunat_description'
            ]);
        });
    }
};
