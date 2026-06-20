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
        Schema::create('pl_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codice_asset')->nullable();
            $table->string('nazione')->nullable();
            $table->string('stato')->nullable();
            $table->string('condizione_asset')->nullable();
            $table->string('utilizzo')->nullable();
            $table->string('emp_id')->nullable();
            $table->string('utente')->nullable();
            $table->string('email')->nullable();
            $table->date('data_allocazione')->nullable();
            $table->string('anydesk_alias')->nullable();
            $table->string('scopo')->nullable();
            $table->string('tipo_allocazione')->nullable();
            $table->date('data_dismesso')->nullable();
            $table->string('motivazione_dismesso')->nullable();
            $table->string('hostName')->nullable();
            $table->string('nome_utente_effetivo')->nullable();
            $table->string('tipo_asset')->nullable()->index();
            $table->string('cpu')->nullable();
            $table->string('cpu_numero')->nullable();
            $table->string('hdd_capienza')->nullable();
            $table->string('hdd_numero')->nullable();
            $table->date('fattura_dt')->nullable();
            $table->string('fattura_numero')->nullable();
            $table->string('ip_address')->nullable();
            $table->date('ultima_data_allocazione')->nullable();
            $table->string('marca')->nullable();
            $table->string('modello')->nullable();
            $table->string('mause')->nullable();
            $table->string('tipo_rete')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->string('ram_numero')->nullable();
            $table->string('ram_memoria')->nullable();
            $table->string('sap_codice_asset')->nullable();
            $table->string('numero_seriale')->nullable();
            $table->date('fine_garanzia')->nullable();
            $table->string('stato_monitoraggio')->nullable();
            $table->dateTime('ultimo_aggiornamento_stato')->nullable();
            $table->boolean('monitoraggio_attivo')->default(false)->index();
           //$table->date('ultima_data_allocazione')->nullable();
            $table->boolean('tag_asset')->nullable();
            $table->dateTime('ultima_notifica')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_assets');
    }
};
