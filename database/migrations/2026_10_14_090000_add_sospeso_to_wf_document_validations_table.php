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
        if (!Schema::hasColumn('wf_document_validations', 'sospeso')) {
            Schema::table('wf_document_validations', function (Blueprint $table) {
                // Pratica sospesa dalla coda Qualita': conserva lo stato corrente
                // e viene esclusa dalla lista/contatore finche' non viene riattivata
                $table->boolean('sospeso')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('wf_document_validations', 'sospeso')) {
            Schema::table('wf_document_validations', function (Blueprint $table) {
                $table->dropColumn('sospeso');
            });
        }
    }
};
