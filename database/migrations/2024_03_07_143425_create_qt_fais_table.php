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
        Schema::create('qt_fais', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->year('anno')->index();
            $table->integer('num');
            $table->dateTime('data_creazione')->index();
            $table->dateTime('data_chiusura')->nullable()->index();
            $table->bigInteger('user')->index();
            $table->integer('risultato')->nullable()->index();
            $table->string('numero_fai')->index();
            $table->text('descrizione')->nullable();
            $table->string('ol')->nullable()->index();
            $table->string('cod_cavo')->nullable()->index();
            $table->string('cod_materiale')->nullable()->index();
            $table->integer('esito')->nullable()->index();
            $table->text('path_drive')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_fais');
    }
};
