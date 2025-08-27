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
        Schema::create('to_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ragione_sociale')->index();
            $table->string('codice_sap')->nullable();
            $table->string('email')->nullable();
            $table->string('telefono')->nullable();
            $table->string('provincia')->nullable();
            $table->string('citta')->nullable();
            $table->string('cap')->nullable();
            $table->string('indirizzo')->nullable();
            $table->boolean('disabled')->default(false);
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_clients');
    }
};
