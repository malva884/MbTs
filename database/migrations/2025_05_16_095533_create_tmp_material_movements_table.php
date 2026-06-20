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
        Schema::create('tmp_material_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('materiale')->index();
            $table->string('um')->nullable();
            $table->date('periodo');
            $table->integer('tipo')->index();
            $table->decimal('quantita',10,2)->default(0.00);
            $table->decimal('valore',10,2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tmp_material_movements');
    }
};
