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
        Schema::table('qt_conformitas', function (Blueprint $table) {
            $table->string('provenienza_fibra')->nullable()->after('google_drive_id'); // 'coloratrici' o 'fornitore'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qt_conformitas', function (Blueprint $table) {
            $table->dropColumn(['provenienza_fibra']);
        });
    }
};
