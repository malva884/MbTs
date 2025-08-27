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
        Schema::create('pr_warehouse_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id')->index();
            $table->foreign('warehouse_id')->references('id')->on('pr_warehouse_heads')->onDelete('cascade');
            $table->string('materiale')->index();
            $table->string('descrizione');
            $table->decimal('quantita', $precision = 10, $scale = 2)->default(0.00);
            $table->integer('fibre')->nullable();
            $table->string('um');
            $table->decimal('valore_unitario', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('valore_totale', $precision = 10, $scale = 2)->default(0.00);
            $table->string('crcy');
            $table->string('ultimo_movimento')->nullable();
            $table->string('classe')->index();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_warehouse_rows');
    }
};
