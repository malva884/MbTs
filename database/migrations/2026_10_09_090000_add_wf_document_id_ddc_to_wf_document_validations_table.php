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
        // La colonna e' referenziata da QtValidationController e ProcessQualityDDCPdf
        // ma potrebbe essere gia' stata aggiunta a mano su qualche ambiente
        if (!Schema::hasColumn('wf_document_validations', 'wf_document_id_ddc')) {
            Schema::table('wf_document_validations', function (Blueprint $table) {
                // ID del documento DDC associato alla validazione del DDT (nullable:
                // una validazione puo' esistere prima che il DDC venga caricato)
                $table->uuid('wf_document_id_ddc')->nullable();
                $table->index('wf_document_id_ddc', 'idx_wf_doc_validations_ddc');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('wf_document_validations', 'wf_document_id_ddc')) {
            Schema::table('wf_document_validations', function (Blueprint $table) {
                $table->dropIndex('idx_wf_doc_validations_ddc');
                $table->dropColumn('wf_document_id_ddc');
            });
        }
    }
};
