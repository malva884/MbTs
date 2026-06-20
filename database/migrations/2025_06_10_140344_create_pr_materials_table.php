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
        Schema::create('pr_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('materiale')->index();
            $table->string('um')->nullable();
            $table->decimal('valore',10,2)->default(0.00);
            $table->string('categorie')->index();
            $table->string('ragruppamento')->nullable()->index();
            $table->date('data_ultimo_movimento');
            $table->date('periodo')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_materials');
    }
};
