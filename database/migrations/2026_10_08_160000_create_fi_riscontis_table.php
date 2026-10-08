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
        Schema::create('fi_riscontis', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['ATTIVO', 'PASSIVO'])->default('ATTIVO');
            $table->string('descrizione');
            $table->decimal('importo_totale', 14, 2);
            $table->date('data_inizio');
            $table->date('data_fine');
            $table->date('data_chiusura_bilancio');
            $table->integer('giorni_totali');
            $table->integer('giorni_competenza');
            $table->integer('giorni_futuri');
            $table->decimal('quota_giornaliera', 14, 4);
            $table->decimal('quota_mensile', 14, 2);
            $table->decimal('quota_competenza', 14, 2);
            $table->decimal('importo_risconto', 14, 2);
            $table->decimal('percentuale_competenza', 5, 2);
            $table->decimal('percentuale_risconto', 5, 2);
            $table->string('conto_dare')->nullable();
            $table->string('conto_avere')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fi_riscontis');
    }
};
