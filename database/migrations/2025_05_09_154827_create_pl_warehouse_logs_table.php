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
        Schema::create('pl_warehouse_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employees_id')->index();
            $table->foreign('employees_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->uuid('magazzino_id');
            $table->foreign('magazzino_id')->references('id')->on('pl_warehouses')->onDelete('cascade');
            $table->integer('quantita')->default(0);
            $table->text('descrizione')->nullable();
            $table->date('data');
            $table->boolean('ritirato')->default(false);
            $table->date('data_ritirato')->nullable();
            $table->boolean('dismesso')->default(false);
            $table->date('data_dismesso')->nullable();
            $table->string('tipo_rete')->nullable();
            $table->string('anydesk_alias')->nullable();
            $table->string('interno')->nullable();
            $table->string('hostName')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->string('sap_codice_asset')->nullable();
            $table->string('numero_seriale')->nullable();
            $table->date('fine_garanzia')->nullable();
            $table->string('stato_monitoraggio')->nullable();
            $table->dateTime('ultimo_aggiornamento_stato')->nullable();
            $table->boolean('monitoraggio_attivo')->default(false)->index();
            $table->string('codice_asset')->nullable();
            $table->string('company_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_warehouse_logs');
    }
};
