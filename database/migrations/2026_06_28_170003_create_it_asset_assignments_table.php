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
        Schema::create('it_asset_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->uuid('employee_id')->index();
            $table->bigInteger('assigned_by')->unsigned()->index();
            $table->integer('assigned_quantity')->default(1);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('returned_at')->nullable();
            $table->integer('returned_quantity')->nullable();
            $table->enum('status', ['Active', 'Returned'])->default('Active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('it_assets');
            $table->foreign('employee_id')->references('id')->on('hr_employees');
            $table->foreign('assigned_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_asset_assignments');
    }
};
