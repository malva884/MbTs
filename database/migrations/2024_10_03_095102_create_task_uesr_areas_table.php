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
        Schema::create('task_uesr_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id')->index();
            $table->foreign('area_id')->references('id')->on('task_areas')->onDelete('cascade');
            $table->bigInteger('user_id');
            $table->boolean('solo_assegnati')->default(true);
            $table->boolean('aprire_task')->default(true);
            $table->boolean('modificare_task')->default(false);
            $table->boolean('chiudere_task')->default(false);
            $table->boolean('eliminare_task')->default(false);
            $table->boolean('responsabile')->default(false);
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_uesr_areas');
    }
};
