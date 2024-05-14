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
        Schema::create('targets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user');
            $table->integer('tipo')->index();
            $table->string('titolo')->index();
            $table->decimal('target', $precision = 14, $scale = 3)->default(0.000);
            $table->decimal('valore', $precision = 14, $scale = 3)->default(0.000);
            $table->uuid('id_riferimento')->nullable()->index();
            $table->date('data_riferimento')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('targets');
    }
};
