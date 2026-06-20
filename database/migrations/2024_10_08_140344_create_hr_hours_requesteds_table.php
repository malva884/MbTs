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
        Schema::create('hr_hours_requesteds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('bacheca_id');
            $table->bigInteger('bacheca_dipendente_id');
            $table->string('dipendente_nome');
            $table->string('dipendente_cognome');
            $table->string('dipendente_matricola');
            $table->boolean('stato')->nullable();
            $table->date('data_richiesta');
            $table->text('note')->nullable();
            $table->integer('tipologia');
            $table->string('centro_di_costo')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_hours_requesteds');
    }
};
