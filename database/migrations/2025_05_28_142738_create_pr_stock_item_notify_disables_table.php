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
        Schema::create('pr_stock_item_notify_disables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('lotto')->index();
            $table->decimal('quantita', 10, 3)->default(0.000);
            $table->string('um');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_stock_item_notify_disables');
    }
};
