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
        Schema::create('hr_hours_requested_details', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('richiesta_id')->index();
            $table->foreign('richiesta_id')->references('id')->on('hr_hours_requesteds')->onDelete('cascade');
            $table->bigInteger('bacheca_id');
            $table->bigInteger('bacheca_dipendente_id');
            $table->string('dipendente_matricola');
            $table->date('data');
            $table->string('ore_richieste')->nullable();
            $table->string('ora_inizio')->nullable();
            $table->string('ora_fine')->nullable();
            $table->integer('tipologia');
            $table->boolean('confermato')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_hours_requested_details');
    }
};
