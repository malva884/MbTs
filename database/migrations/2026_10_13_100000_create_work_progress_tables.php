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
        Schema::create('work_status_heads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->smallInteger('status')->default(0); // 0=Import, 1=Processing, 2=Completed, 3=Error
            $table->decimal('total_final_mp', 14, 2)->default(0);
            $table->decimal('total_mp', 14, 2)->default(0);
            $table->decimal('total_old', 14, 2)->default(0);
            $table->decimal('total_final', 14, 2)->default(0);
            $table->decimal('total_raw_material', 14, 2)->default(0);
            $table->decimal('total_raw_material2', 14, 2)->default(0);
            $table->decimal('total_machine_manpower_cost', 14, 2)->default(0);
            $table->decimal('total_machine_manpower_cost2', 14, 2)->default(0);
            $table->decimal('raw_material_machine_manpower_cost', 14, 2)->default(0);
            $table->text('import_error')->nullable();
            $table->timestamps();
        });

        Schema::create('work_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('head');
            $table->foreign('head')->references('id')->on('work_status_heads')->onDelete('cascade');
            $table->string('order')->nullable();
            $table->string('father')->nullable();
            $table->bigInteger('order_row')->default(0);
            $table->string('material_1')->nullable();
            $table->string('material_description_1')->nullable();
            $table->string('division')->nullable();
            $table->decimal('total_order_quantity', 14, 3)->nullable();
            $table->string('um')->nullable();
            $table->decimal('quantity_1', 14, 3)->nullable();
            $table->string('operation_activity')->nullable();
            $table->string('work_center')->nullable();
            $table->decimal('work_center_cost', 14, 2)->nullable();
            $table->decimal('confirmed_quantity', 14, 3)->nullable();
            $table->decimal('activity_machine_hours', 14, 2)->nullable();
            $table->decimal('machine_cost', 14, 2)->nullable();
            $table->decimal('activity_manpower_hours', 14, 2)->nullable();
            $table->decimal('manpower_cost', 14, 2)->nullable();
            $table->decimal('machine_cost_manpower', 14, 2)->nullable();
            $table->decimal('calculation_1', 12, 4)->nullable();
            $table->string('material_2')->nullable();
            $table->string('material_description_2')->nullable();
            $table->decimal('requirement_quantity', 14, 3)->nullable();
            $table->string('base_unit_measure')->nullable();
            $table->decimal('quantity_2', 14, 3)->nullable();
            $table->decimal('material_cost', 14, 2)->nullable();
            $table->decimal('cost_material', 14, 2)->nullable();
            $table->decimal('calculation_2', 12, 4)->nullable();
            $table->decimal('total_raw_material', 14, 2)->nullable();
            $table->decimal('total_machine_cost_manpower', 14, 2)->nullable();
            $table->decimal('final_total', 14, 2)->nullable();
            $table->decimal('raw_material_machine_manpower_cost', 14, 2)->nullable();
            $table->decimal('result_1', 14, 2)->nullable();
            $table->decimal('result_2', 14, 2)->nullable();
            $table->decimal('qty_perv_fase', 14, 3)->nullable();
            $table->string('um_fase')->nullable();
            $table->date('date_created')->nullable();
            $table->boolean('check_row')->default(false);
            $table->boolean('view')->default(false);
            $table->timestamps();

            $table->index(['head', 'father']);
            $table->index(['head', 'check_row']);
            $table->index(['head', 'view']);
        });

        Schema::create('work_course_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('order')->nullable();
            $table->uuid('head');
            $table->foreign('head')->references('id')->on('work_status_heads')->onDelete('cascade');
            $table->decimal('total_material', 14, 2)->default(0);
            $table->decimal('total_consuntivo', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('machine_labor_costs', function (Blueprint $table) {
            $table->id();
            $table->string('machine');
            $table->decimal('cost', 14, 2)->default(0);
            $table->decimal('manpower_cost', 14, 2)->nullable();
            $table->date('date_import');
            $table->timestamps();

            $table->index(['machine', 'date_import']);
        });

        Schema::create('materials_costs', function (Blueprint $table) {
            $table->id();
            $table->string('material');
            $table->decimal('cost', 14, 4)->default(0);
            $table->date('date_import');
            $table->timestamps();

            $table->index(['material', 'date_import']);
        });

        Schema::create('ws_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ws_settings');
        Schema::dropIfExists('materials_costs');
        Schema::dropIfExists('machine_labor_costs');
        Schema::dropIfExists('work_course_summaries');
        Schema::dropIfExists('work_statuses');
        Schema::dropIfExists('work_status_heads');
    }
};
