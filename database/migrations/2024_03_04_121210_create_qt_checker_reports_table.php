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
        Schema::create('qt_checker_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user')->index();
            $table->integer('ol')->index();
            $table->dateTime('date_create')->index();
            $table->integer('num_fo');
            $table->string('coil');
            $table->integer('fo_try');
            $table->string('stage')->index();
            $table->boolean('not_conformity')->default(false)->index();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_checker_reports');
    }
};
