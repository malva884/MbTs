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
        Schema::create('registro_account_wifis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('register_id')->index();
            $table->bigInteger('user')->index();
            $table->string('nome')->index();
            $table->string('email')->index();
            $table->string('username');
            $table->string('password');
            $table->string('azienda')->index();
            $table->date('data_inizio ')->index();
            $table->date('data_fine')->nullable();
            $table->boolean('stato')->index();
            $table->string('rete')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_account_wifis');
    }
};
