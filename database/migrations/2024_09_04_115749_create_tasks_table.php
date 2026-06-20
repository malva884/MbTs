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
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id')->index();
            $table->foreign('area_id')->references('id')->on('task_areas')->onDelete('cascade');
            $table->uuid('padre')->null()->index();
            $table->bigInteger('responsabile_id');
            $table->bigInteger('utente_id');
            $table->string('codice')->index();
            $table->integer('stato')->index();
            $table->uuid('reparto_id')->nullable();
            $table->uuid('mansione_id')->nullable();
            $table->string('titolo');
            $table->text('descrizione')->nullable();
            $table->date('data_chiusura')->nullable();
            $table->date('data_scadenza');
            $table->date('data_scadenza_iniziale');
            $table->string('giorni_dopo_scadenza')->nullable();
            $table->integer('completamento');
            $table->integer('priorieta');
            $table->integer('numero');
            $table->uuid('near_miss_id')->nullable();
            $table->text('path_drive')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
