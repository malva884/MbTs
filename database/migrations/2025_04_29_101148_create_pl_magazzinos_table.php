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
        Schema::create('pl_magazzinos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('marca');
            $table->text('descrizione')->nullable();
            $table->string('tipologia')->index();
            $table->string('pn_interno')->nullable();
            $table->string('pn_oem')->nullable();
            $table->integer('quantita_minima')->default(0);
            $table->integer('quantita')->default(0);
            $table->date('data_fornitura');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_magazzinos');
    }
};
