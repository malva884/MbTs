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
        Schema::create('recipient_coordinates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('citta')->index();
            $table->string('cap')->index();
            $table->string('indirizzo')->nullable();
            $table->string('latitudine');
            $table->string('longitudine');
            $table->string('km');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipient_coordinates');
    }
};
