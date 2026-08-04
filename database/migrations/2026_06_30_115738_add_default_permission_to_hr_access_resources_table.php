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
        Schema::table('hr_access_resources', function (Blueprint $table) {
            $table->string('default_permission')->default('read')->after('disabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_access_resources', function (Blueprint $table) {
            $table->dropColumn('default_permission');
        });
    }
};
