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
        Schema::create('pl_warehouse_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('magazzino_id')->index();
            $table->foreign('magazzino_id')->references('id')->on('pl_warehouses')->onDelete('cascade');
            $table->string('tipologia')->nullable();
            $table->text('sito')->nullable();
            $table->text('link')->nullable();
            $table->decimal('prezzo', $precision = 10, $scale = 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_warehouse_infos');
    }
};
