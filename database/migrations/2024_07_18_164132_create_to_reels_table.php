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
        Schema::create('to_reels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bobina');
            $table->integer('capacita');
            $table->decimal('m3', $precision = 10, $scale = 3)->default(0.000);
            $table->string('codice_as')->nullable();
            $table->decimal('costo', $precision = 10, $scale = 4)->default(0.0000);
            $table->decimal('costo_medio', $precision = 10, $scale = 4)->default(0.0000);
            $table->string('peso');
            $table->string('dimensioni');
            $table->string('lettera');
            $table->string('company_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_reels');
    }
};
