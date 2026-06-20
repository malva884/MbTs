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
        Schema::create('pr_stock_lot_notifies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('materiale')->index();
            $table->string('lotto')->index();
            $table->decimal('quantita', 14, 5);
            $table->string('um');
            $table->boolean('notifica')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_stock_lot_notifies');
    }
};
