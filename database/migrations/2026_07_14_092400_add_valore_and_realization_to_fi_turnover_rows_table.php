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
        Schema::table('fi_turnover_rows', function (Blueprint $table) {
            $table->decimal('valore_unitario', 10, 3)->default(0.000)->after('fkm');
            $table->decimal('valore_totale', 10, 3)->default(0.000)->after('valore_unitario');
            $table->decimal('realization', 10, 3)->default(0.000)->after('valore_totale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fi_turnover_rows', function (Blueprint $table) {
            $table->dropColumn(['valore_unitario', 'valore_totale', 'realization']);
        });
    }
};
