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
        Schema::create('to_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('materiale');
            $table->string('descrizione')->nullable();
            $table->string('diametro', )->nullable();
            $table->decimal('costo', $precision = 12, $scale = 4)->default(0.0000);
            $table->boolean('disabled')->default(false);
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_materials');
    }
};
