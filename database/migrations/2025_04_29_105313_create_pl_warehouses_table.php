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
        Schema::create('pl_warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('marca');
            $table->string('modello');
            $table->text('descrizione')->nullable();
            $table->string('tipologia')->index();
            $table->string('pn_interno')->nullable();
            $table->string('pn_oem')->nullable();
            $table->integer('quantita_minima')->default(0);
            $table->integer('quantita')->default(0);
            $table->date('data_fornitura')->nullable();
            $table->decimal('prezzo', $precision = 10, $scale = 2)->default(0.00);
            $table->string('cpu')->nullable();
            $table->string('cpu_numero')->nullable();
            $table->string('hdd_capienza')->nullable();
            $table->string('ram_numero')->nullable();
            $table->string('ram')->nullable();
            $table->string('wifi')->nullable();
            $table->string('wifi_tipologia')->nullable();
            $table->string('display')->nullable();
            $table->string('company_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_warehouses');
    }
};
