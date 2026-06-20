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
        Schema::create('pr_warehouse_bis', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('id_material')->index();
            $table->string('materiale')->index();
            $table->string('descrizione')->nullable();
            $table->string('um')->nullable();
            $table->decimal('quantita',10,2)->default(0.00);
            $table->decimal('valore_uni',10,2)->default(0.00);
            $table->decimal('totole',10,2)->default(0.00);
            $table->string('categorie')->index();
            $table->date('data_ultimo_movimento');
            $table->integer('days_last_movement')->default(0);
            $table->string('range_last_moviment');
            $table->year('anno');
            $table->integer('mese');
            $table->integer('settimana');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_warehouse_bis');
    }
};
