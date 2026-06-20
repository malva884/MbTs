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
        Schema::create('hr_employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome');
            $table->string('cognome');
            $table->string('matricola');
            $table->string('nome_completo');
            $table->string('sesso');
            $table->string('email');
            $table->date('data_assunzione')->nullable();
            $table->date('data_nascita')->nullable();
            $table->date('data_ultima_visita')->nullable();
            $table->date('data_scadenza_visita')->nullable();
            $table->integer('numero_anni_visita_medica')->default(4);
            $table->string('tel')->nullable();
            $table->string('tel_az')->nullable();
            $table->text('avatar')->nullable();
            $table->boolean('dimesso')->default(false);
            $table->text('path_drive')->nullable();
            $table->boolean('valutatore')->default(false);
            $table->uuid('ruolo_id')->index();
            $table->uuid('reparto_id')->index();
            $table->uuid('centro_id')->index();
            $table->string('company_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_employees');
    }
};
