<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_competency_evaluations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('hr_employees')->cascadeOnDelete();
            $table->foreignUuid('activity_id')->constrained('hr_competency_activities')->cascadeOnDelete();
            $table->unsignedBigInteger('valutatore_id')->nullable()->index();
            $table->unsignedTinyInteger('valutazione')->default(0);
            $table->date('data_valutazione')->nullable();
            $table->unsignedSmallInteger('anno')->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'activity_id', 'anno'], 'hr_comp_eval_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_competency_evaluations');
    }
};
