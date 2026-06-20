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
        Schema::create('pr_product_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codice');
            $table->string('descrizione_it')->nullable();
            $table->string('descrizione_en')->nullable();
            $table->string('tipologia');
            $table->boolean('disattivo')->default(false)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_product_codes');
    }
};
