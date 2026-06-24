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
        Schema::create('hr_employee_training_mandatories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->uuid('formazione_id')->index();
            $table->foreign('formazione_id')->references('id')->on('hr_trainings')->cascadeOnDelete();
            $table->date('data_formazione')->nullable(); // Nullable: un corso può essere pianificato/assegnato prima di essere svolto
            $table->date('data_scadenza')->nullable();   // Nullable: calcolato solo se il corso è stato effettivamente svolto
            $table->text('path_drive')->nullable();
            $table->unsignedBigInteger('utente_id')->nullable()->index(); // Corretto a unsignedBigInteger e indicizzato per audit log/join; nullable per CLI/Job di sistema
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_training_mandatories');
    }
};
