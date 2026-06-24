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
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('cognome');
            $table->string('matricola')->unique(); // Unica e indicizzata per ricerche rapide e integrità
            $table->string('nome_completo');
            $table->char('sesso', 1)->nullable(); // CHAR(1) per risparmiare spazio rispetto a VARCHAR(255)
            $table->string('email')->nullable()->index(); // Email indicizzata per eventuali ricerche/autenticazioni
            $table->date('data_assunzione')->nullable();
            $table->date('data_nascita')->nullable();
            $table->date('data_ultima_visita')->nullable();
            $table->date('data_scadenza_visita')->nullable();
            $table->integer('numero_anni_visita_medica')->default(4);
            $table->string('tel', 30)->nullable();
            $table->string('tel_az', 30)->nullable();
            $table->text('avatar')->nullable();
            $table->boolean('dimesso')->default(false)->index(); // Indicizzato: quasi tutte le query filtrano dipendenti attivi
            $table->text('path_drive')->nullable();
            $table->boolean('valutatore')->default(false)->index(); // Indicizzato per filtrare rapidamente i valutatori di competenze
            $table->uuid('ruolo_id')->index();
            $table->uuid('reparto_id')->index();
            $table->uuid('centro_id')->index();
            $table->string('company_id')->index();
            $table->timestamps();

            // Vincoli di integrità referenziale (no action equivale a restrict in SQL Server)
            $table->foreign('centro_id')->references('id')->on('hr_cost_centers')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
