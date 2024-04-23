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
        Schema::create('qt_type_tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('fai')->nullable()->index();
            $table->bigInteger('user')->index();
            $table->integer('ol')->index();
            $table->string('materiale')->index();
            $table->text('descrizione')->nullable();
            $table->string('esito')->nullable()->index();
            $table->string('standard')->index();
            $table->string('specifica')->nullable()->index();
            $table->integer('tipo')->index();
            $table->date('data_prova')->index();
            $table->text('path_drive')->nullable();
            $table->string('cliente')->nullable()->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_type_tests');
    }
};
