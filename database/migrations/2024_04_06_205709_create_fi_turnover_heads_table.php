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
        Schema::create('fi_turnover_heads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user');
            $table->boolean('import');
            $table->boolean('calcolato')->default(false);
            $table->year('anno')->index();
            $table->integer('mese')->index();
            $table->boolean('storege')->default(false);
            $table->decimal('totale_fatturato', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('target_cc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('target_ofc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('target_kfkm', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('target_ckm', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('value_cc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('value_ofc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('value_kfkm', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('value_ckm', $precision = 14, $scale = 3)->default(0.000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fi_turnover_heads');
    }
};
