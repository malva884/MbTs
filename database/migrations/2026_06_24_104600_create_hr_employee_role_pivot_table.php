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
        // Creazione tabella pivot molti-a-molti tra Dipendenti e Ruoli (Spatie roles)
        Schema::create('hr_employee_role', function (Blueprint $table) {
            $table->uuid('employee_id');
            $table->unsignedBigInteger('role_id');

            // Chiave primaria composta per prevenire duplicati
            $table->primary(['employee_id', 'role_id']);

            // Vincoli di integrità referenziale
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // Rimuoviamo il vecchio campo singolo ruolo_id da hr_employees
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropIndex(['ruolo_id']);
            $table->dropColumn('ruolo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->uuid('ruolo_id')->nullable()->index();
        });

        Schema::dropIfExists('hr_employee_role');
    }
};
