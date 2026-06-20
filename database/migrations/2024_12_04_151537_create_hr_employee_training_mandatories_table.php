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
            $table->uuid('employee_id')->index();
            $table->foreign('employee_id')->references('id')->on('hr_employees')->onDelete('cascade');
            $table->bigInteger('formazione_id')->index();
            $table->date('data_formazione');
            $table->date('data_scadenza');
            $table->text('path_drive')->nullable();
            $table->bigInteger('utente_id');
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
