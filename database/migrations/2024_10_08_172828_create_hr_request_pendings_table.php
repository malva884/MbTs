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
        Schema::create('hr_request_pendings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('richiesta_id')->index();
            $table->foreign('richiesta_id')->references('id')->on('hr_hours_requesteds')->onDelete('cascade');
            $table->bigInteger('user_id');
            $table->text('approvatore')->nullable();
            $table->boolean('stato')->nullable();
            $table->integer('livello');
            $table->text('nota')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_request_pendings');
    }
};
