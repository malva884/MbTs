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
        Schema::create('wf_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Colonne per la relazione polimorfica
            $table->string('model');
            $table->uuid('model_id');

            // Gerarchia superiore (es. ID dell'azienda o del datore di lavoro)
            $table->uuid('model_head_id')->nullable()->index();

            $table->string('riferimento')->nullable();
            $table->string('nome_file');
            $table->string('tipologia')->nullable()->index(); // Indicizzato se cerchi per tipo

            // Cambiato da text a string per performance e futura indicizzabilità
            $table->string('id_file_drive', 128)->nullable();

            $table->timestamps();

            // INDICE COMPOSITO: Ottimizza al massimo la ricerca polimorfica standard
            $table->index(['model', 'model_id', 'tipologia'], 'wf_docs_model_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_documents');
    }
};
