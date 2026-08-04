<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_competency_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('attivita');
            $table->boolean('disattivo')->default(false);
            $table->timestamps();

            $table->index('disattivo');
        });

        Schema::create('hr_competency_activity_role', function (Blueprint $table) {
            $table->uuid('activity_id');
            $table->uuid('hr_role_id');
            $table->unsignedTinyInteger('valutazione_ideale')->default(0);

            $table->primary(['activity_id', 'hr_role_id']);

            $table->foreign('activity_id')->references('id')->on('hr_competency_activities')->onDelete('cascade');
            $table->foreign('hr_role_id')->references('id')->on('hr_roles')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_competency_activity_role');
        Schema::dropIfExists('hr_competency_activities');
    }
};
