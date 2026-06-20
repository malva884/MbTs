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
        Schema::create('task_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('area');
            $table->bigInteger('responsabile_id');
            $table->string('sigla');
            $table->string('tipologia');
            $table->text('cartella_drive')->nullable();
            $table->boolean('nascosta')->default(false);
            $table->text('colore')->nullable();
            $table->boolean('approvazione_task')->default(true);
            $table->boolean('approvazione_sub_task')->default(true);
            $table->boolean('notifiche')->default(true);
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_areas');
    }
};
