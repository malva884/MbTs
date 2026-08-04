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
        Schema::table('pr_movements', function (Blueprint $table) {
            $table->string('uni_key')->nullable()->unique()->index()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_movements', function (Blueprint $table) {
            $table->dropUnique(['uni_key']);
            $table->dropColumn('uni_key');
        });
    }
};
