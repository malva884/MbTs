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
        Schema::create('qt_fais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codice')->unique();        // Generato in automatico (es. FAI-2026-0001)
            $table->date('data_inizio');               // DATA INIZIO ATTIVITA'
            $table->text('descrizione');            // DESCRIZIONE FAI
            $table->string('esito_fattibilita')->nullable(); // ESITO ESAME DI FATTIBILITA' (?)
            $table->string('soggetto');                // CLIENTE / FORNITORE
            $table->string('articolo');                // DESCRIZIOE ARTICOLO
            $table->string('specifica')->nullable();   // SPECIFICA TECNICA
            $table->string('ol');                      // NUMERO OL
            $table->json('prove');                     // PROVE DA FARE (elenco da selezionare)
            $table->enum('esito', ['POSITIVO', 'NEGATIVO', 'ANNULLATO', 'IN_CORSO'])->default('IN_CORSO'); // ESITO FINALE FAI
            $table->string('drive_id')->nullable();    // ID cartella Google Drive per i documenti
            $table->string('specifica_id')->nullable();    // ID file Google Drive per specifica
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_fais');
    }
};
