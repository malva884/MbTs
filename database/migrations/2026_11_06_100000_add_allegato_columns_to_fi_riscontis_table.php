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
        Schema::table('fi_riscontis', function (Blueprint $table) {
            $table->string('google_drive_file_id')->nullable()->after('note');
            $table->string('allegato_nome')->nullable()->after('google_drive_file_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fi_riscontis', function (Blueprint $table) {
            $table->dropColumn(['google_drive_file_id', 'allegato_nome']);
        });
    }
};
