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
        Schema::create('wf_document_validations', function (Blueprint $table) {
            // Chiave primaria della tabella di validazione (UUID)
            $table->uuid('id')->primary();

            // ID del documento generico (WfDocument) a cui è legata questa validazione
            $table->uuid('wf_document_id');

            // ID dell'utente (della Qualità) che ha eseguito l'azione
            $table->bigInteger('user_id');

            // Il reparto competente, impostato di default su 'Qualita'
            $table->string('reparto', 100)->default('Qualita');

            // Lo stato dell'approvazione (es. 'APPROVATO', 'RIFIUTATO')
            $table->string('stato', 50);

            // La tipologia controllata (es. 'Idoneita' o 'Giudizio')
            $table->string('tipologia_validazione', 100);

            // created_at e updated_at (DATETIME su SQL Server)
            $table->timestamps();

            // -----------------------------------------------------------------
            // INDICI (Ottimizzati per SQL Server)
            // -----------------------------------------------------------------

            // 1. Indice principale per quando filtriamo le validazioni di un documento
            $table->index('wf_document_id', 'idx_wf_doc_validations_doc');

            // 2. Indice composito per il tuo controller: velocizza la ricerca combinata
            // di reparto + stato (es. quando cerchi solo le approvazioni della 'Qualita')
            $table->index(['reparto', 'stato'], 'idx_wf_doc_validations_dept_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_document_validations');
    }
};
