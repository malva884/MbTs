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
        Schema::create('fi_shipped_rows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('head');
            $table->foreign('head')->references('id')->on('fi_shipped_heads')->onDelete('cascade');
            $table->date('date_row');
            $table->string('code_client');
            $table->string('client')->index();
            $table->string('item');
            $table->string('material')->index();
            $table->string('description');
            $table->integer('type')->index();
            $table->string('commessa')->index();
            $table->string('code_recipient');
            $table->string('recipient')->index();
            $table->string('unit');
            $table->decimal('qty_value', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('cost_value', $precision = 10, $scale = 2)->default(0.00);
            $table->integer('fiber_counter')->index();
            $table->decimal('delivered_qty', $precision = 10, $scale = 3)->default(0.000);
            $table->decimal('qty_fkm', $precision = 10, $scale = 3)->default(0.000);
            $table->decimal('price_km', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('cost_km', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('std_price', $precision = 10, $scale = 2)->default(0.00);
            $table->string('order')->nullable()->index();
            $table->decimal('net_profit', $precision = 10, $scale = 2)->default(0.00);
            $table->decimal('profit_perc', $precision = 10, $scale = 2)->default(0.00);
            $table->integer('exchange_rate')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('document')->nullable();
            $table->integer('km_distance')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fi_shipped_rows');
    }
};
