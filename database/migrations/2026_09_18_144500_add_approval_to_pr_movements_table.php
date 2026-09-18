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
            $table->string('stato')->nullable()->index()->after('uni_key');
            $table->date('data_approvazione')->nullable()->after('stato');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pr_movements', function (Blueprint $table) {
            $table->dropIndex(['stato']);
            $table->dropColumn(['stato', 'data_approvazione']);
        });
    }
};
