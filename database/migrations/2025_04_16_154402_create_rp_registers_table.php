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
        Schema::create('rp_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user')->index();
            $table->boolean('attivo')->default(true)->index();
            $table->string('email')->index();
            $table->string('nome')->nullable();
            $table->string('azienda')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_registers');
    }
};
