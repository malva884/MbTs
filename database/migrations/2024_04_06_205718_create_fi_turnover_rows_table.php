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
        Schema::create('fi_turnover_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('head');
            $table->foreign('head')->references('id')->on('fi_turnover_heads')->onDelete('cascade');
            $table->decimal('quantita', $precision = 10, $scale = 3)->default(0.000);
            $table->string('unit')->nullable();
            $table->string('materiale')->nullable()->index();
            $table->decimal('importo_valuta_locale', $precision = 10, $scale = 2)->default(0.00);
            $table->string('documento_numero')->index();
            $table->string('documento_tipo')->index();
            $table->string('cliente');
            $table->string('tipologia_cavo')->index();
            $table->date('data_documento')->index();
            $table->date('data_publicazione')->index();
            $table->string('chiave_publicazione');
            $table->string('valuta_locale');
            $table->string('tax_code')->nullable();
            $table->string('account_tipo');
            $table->string('account')->index();
            $table->string('codice_cliente')->index();
            $table->boolean('check')->default(false)->index();
            $table->decimal('ckm', $precision = 10, $scale = 3)->default(0.000);
            $table->decimal('kfkm', $precision = 10, $scale = 3)->default(0.000);
            $table->string('paese')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fi_turnover_rows');
    }
};
