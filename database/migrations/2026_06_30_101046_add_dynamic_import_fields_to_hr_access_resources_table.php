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
            $table->string('server_ip')->nullable()->after('drive_file_id');
            $table->string('import_method')->nullable()->after('server_ip')->default('manual');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_access_resources', function (Blueprint $table) {
            $table->dropColumn(['server_ip', 'import_method']);
        });
    }
};
