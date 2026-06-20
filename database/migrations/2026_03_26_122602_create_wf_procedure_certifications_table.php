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
        Schema::create('wf_procedure_certifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('procedura_id')->index();
            $table->foreign('procedura_id')->references('id')->on('wf_procedures')->onDelete('cascade');
            $table->uuid('cartificazione_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_procedure_certifications');
    }
};
