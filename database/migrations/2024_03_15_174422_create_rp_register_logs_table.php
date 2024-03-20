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
        Schema::create('rp_register_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('evento_id')->index();
            $table->string('cod_riferimento')->index();
            $table->string('cod_tessera')->nullable()->index();
            $table->bigInteger('user')->index();
            $table->dateTime('data_prevista')->index();
            $table->dateTime('data_scadenza')->nullable();
            $table->boolean('attivo')->default(true)->index();
            $table->string('email')->index();
            $table->string('nome')->nullable();
            $table->boolean('wifi')->default(false)->nullable();
            $table->string('password_wifi')->nullable();
            $table->string('username_wifi')->nullable();
            $table->boolean('notifica_inviata')->default(false)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_register_logs');
    }
};
