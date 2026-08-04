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
        Schema::table('it_asset_assignments', function (Blueprint $table) {
            // Add polymorphic columns for assignable (Employee or Machine)
            $table->string('assignable_type')->nullable()->after('employee_id');
            $table->uuid('assignable_id')->nullable()->after('assignable_type');
            
            // Add index for polymorphic queries
            $table->index(['assignable_type', 'assignable_id']);
            
            // Make employee_id nullable since it will be replaced by polymorphic relationship
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id']);
            $table->uuid('employee_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('it_asset_assignments', function (Blueprint $table) {
            $table->dropIndex(['assignable_type', 'assignable_id']);
            $table->dropColumn(['assignable_type', 'assignable_id']);
            
            // Restore employee_id as not nullable
            $table->uuid('employee_id')->nullable(false)->change();
            $table->foreign('employee_id')->references('id')->on('hr_employees');
            $table->index('employee_id');
        });
    }
};
