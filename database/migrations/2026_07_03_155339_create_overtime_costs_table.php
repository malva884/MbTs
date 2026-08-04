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
        Schema::create('overtime_costs', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->string('cost_center_group');
            $table->integer('week_number');
            $table->decimal('hours', 10, 2)->default(0);
            $table->decimal('cost', 15, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['year', 'month', 'cost_center_group', 'week_number'], 'unique_overtime_record');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_costs');
    }
};
