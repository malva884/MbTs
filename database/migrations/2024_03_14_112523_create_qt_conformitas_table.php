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
        Schema::create('qt_conformitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('report_id')->nullable();
            $table->bigInteger('user')->index();
            $table->string('ol')->index();
            $table->string('materiale')->index();
            $table->string('bobina');
            $table->string('physical_l')->nullable();
            $table->string('optical_l')->nullable();
            $table->string('stage')->nullable();
            $table->string('macchina')->nullable();
            $table->string('difetto')->nullable();
            $table->string('fibre')->nullable();
            $table->string('soluzione')->nullable();
            $table->boolean('chiuso')->default(false);
            $table->string('note')->nullable();
            $table->string('diametro')->nullable();
            $table->integer('num_fo')->nullable();
            $table->string('tipologia_fibra')->nullable();
            $table->string('tipologia_difetto')->nullable();
            $table->string('operator')->nullable();
            $table->dateTime('data_apertura');
            $table->dateTime('data_chiusura')->nullable();
            $table->year('anno')->index();
            $table->string('numero')->index();
            $table->integer('time')->nullable();
            $table->string('google_drive_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_conformitas');
    }
};
