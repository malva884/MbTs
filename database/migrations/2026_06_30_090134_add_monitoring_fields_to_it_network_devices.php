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
        Schema::table('it_network_devices', function (Blueprint $table) {
            $table->boolean('monitor_enabled')->default(false)->after('disabled');
            $table->enum('status', ['online', 'offline', 'unknown'])->default('unknown')->after('monitor_enabled');
            $table->timestamp('last_check_at')->nullable()->after('status');
            $table->timestamp('last_online_at')->nullable()->after('last_check_at');
            $table->integer('response_time_ms')->nullable()->after('last_online_at');
            $table->decimal('uptime_percentage', 5, 2)->default(0)->after('response_time_ms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('it_network_devices', function (Blueprint $table) {
            $table->dropColumn([
                'monitor_enabled',
                'status',
                'last_check_at',
                'last_online_at',
                'response_time_ms',
                'uptime_percentage',
            ]);
        });
    }
};
