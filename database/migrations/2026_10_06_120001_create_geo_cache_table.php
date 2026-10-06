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
        Schema::create('geo_cache', function (Blueprint $table) {
            $table->id();
            $table->string('chiave', 64)->unique();
            $table->string('indirizzo_ricerca', 500);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('paese', 100)->nullable();
            $table->string('country_code', 8)->nullable();
            $table->string('display_name', 700)->nullable();
            $table->string('tipo', 50)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geo_cache');
    }
};
