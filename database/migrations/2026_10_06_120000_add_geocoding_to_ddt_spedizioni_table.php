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
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->decimal('destinazione_lat', 10, 7)->nullable()->after('destinazione_regione');
            $table->decimal('destinazione_lng', 10, 7)->nullable()->after('destinazione_lat');
            $table->string('destinazione_paese', 100)->nullable()->after('destinazione_lng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->dropColumn([
                'destinazione_lat',
                'destinazione_lng',
                'destinazione_paese',
            ]);
        });
    }
};
