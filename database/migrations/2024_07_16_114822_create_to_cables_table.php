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
        Schema::create('to_cables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codice');
            $table->uuid('categoria_id')->index();
            $table->string('categoria');
            $table->string('descrizione')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_cables');
    }
};
