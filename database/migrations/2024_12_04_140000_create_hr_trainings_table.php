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
        Schema::create('hr_trainings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('formazione')->unique(); // Indice univoco per evitare corsi duplicati e velocizzare ricerche esatte/ordinamenti
            $table->string('tipologia', 30)->default('obbligatoria')->index(); // 'obbligatoria' o 'professionale'
            $table->boolean('obbligatorio')->default(false)->index(); // Indicizzato per filtrare rapidamente i corsi obbligatori
            $table->boolean('auto_creazione')->default(false)->index(); // Indicizzato per filtrare rapidamente i corsi a creazione automatica
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_trainings');
    }
};
