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
        Schema::create('hr_employee_training_professionals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->uuid('formazione_id')->nullable()->index();
            $table->foreign('formazione_id')->references('id')->on('hr_trainings')->nullOnDelete(); // Collegamento facoltativo al catalogo corsi
            $table->string('formazione')->nullable(); // Nome del corso come testo libero fallback se non presente a catalogo
            $table->date('data_formazione')->index(); // Indicizzato per velocizzare le ricerche cronologiche e i report
            $table->text('path_drive')->nullable();
            $table->unsignedBigInteger('utente_id')->nullable()->index(); // Corretto a unsignedBigInteger, indicizzato e nullable per audit log/CLI/Job di sistema
            $table->unsignedTinyInteger('tipologia')->index(); // Ottimizzato a tinyInteger per risparmiare spazio (visto che i valori sono piccoli interi, es: 1-4) e indicizzato
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_training_professionals');
    }
};
