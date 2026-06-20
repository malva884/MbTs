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
        Schema::create('pr_warehouse_heads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user')->index();
            $table->string('titolo');
            $table->year('anno')->index();
            $table->string('mese')->index();
            $table->date('data_riferimento')->index();
            $table->decimal('totale', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('fkm_ofc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('ckm_ofc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('ckm_cc', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('corso_lavori', $precision = 10, $scale = 2)->default(0.00);
            $table->string('company_id')->nullable();
            $table->boolean('calcolato')->default(false);
            $table->string('path_drive')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_warehouse_heads');
    }
};
