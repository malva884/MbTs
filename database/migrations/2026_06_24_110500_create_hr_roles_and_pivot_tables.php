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
        // 1. Creiamo la tabella specifica per i Ruoli dei Dipendenti (mansioni/ruoli HR)
        Schema::create('hr_roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ruolo');
            $table->boolean('disattivo')->default(false);
            $table->timestamps();
        });

        // 2. Rimuoviamo la vecchia tabella pivot temporanea che puntava ai ruoli di Spatie
        Schema::dropIfExists('hr_employee_role');

        // 3. Creiamo la nuova tabella pivot corretta tra Dipendenti e Ruoli HR (entrambi UUID)
        Schema::create('hr_employee_role', function (Blueprint $table) {
            $table->uuid('employee_id');
            $table->uuid('role_id');

            $table->primary(['employee_id', 'role_id']);

            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('hr_roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employee_role');
        Schema::dropIfExists('hr_roles');
    }
};
