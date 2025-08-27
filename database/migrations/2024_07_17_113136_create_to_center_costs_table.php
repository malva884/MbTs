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
        Schema::create('to_center_costs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('centro');
            $table->decimal('costo', $precision = 10, $scale = 2)->default(0.00);
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
        Schema::dropIfExists('to_center_costs');
    }
};
