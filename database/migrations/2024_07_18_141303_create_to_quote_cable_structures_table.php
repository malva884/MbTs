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
        Schema::create('to_quote_cable_structures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cavo_id')->index();
            $table->foreign('cavo_id')->references('id')->on('to_quote_cables')->onDelete('cascade');
            $table->string('centro')->nullable();
            $table->string('materiale')->nullable();
            $table->string('descrizione')->nullable();
            $table->decimal('diametro', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('peso', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('ordinata', $precision = 10, $scale = 2)->default(0.00);
            $table->integer('elementi')->nullable();
            $table->integer('posizione')->nullable();
            $table->decimal('costo', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('costo_materia_prima', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('costo_lavorazione', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('ore_macchina', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('costo_centro', $precision = 10, $scale = 2)->default(0.00);
            $table->text('nota')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_quote_cable_structures');
    }
};
