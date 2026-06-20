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
        Schema::create('to_quote_cables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('preventivo_id')->index();
            $table->foreign('preventivo_id')->references('id')->on('to_quotes')->onDelete('cascade');
            $table->uuid('cavo_id')->nullable();
            $table->string('codice');
            $table->string('descrizione')->nullable();
            $table->float('metri')->default(0);
            $table->float('scarto')->default(0);
            $table->decimal('costo_scarto', $precision = 10, $scale = 4)->default(0.0000);
            $table->float('diametro')->default(0);
            $table->uuid('bobina_id')->nullable();
            $table->string('bobina');
            $table->integer('bobina_numero');
            $table->decimal('costo_bobina', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('totale_costo_bobine', $precision = 10, $scale = 4)->default(0.0000);
            $table->float('peso')->default(0);
            $table->float('peso_materie')->default(0);
            $table->decimal('m3', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('m3_totale', $precision = 10, $scale = 2)->default(0.00);
            $table->float('pezzatura')->default(0);
            $table->decimal('costo', $precision = 12, $scale = 4)->default(0.0000);
            $table->decimal('parametro', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('costo_manodopera', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('somma_materiali', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('costo_materiali', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('netto', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('lordo', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('variante_rame', $precision = 10, $scale = 4)->default(0.0000);
            $table->boolean('calcolato')->default(false);
            $table->integer('posizione');
            $table->string('nota')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_quote_cables');
    }
};
