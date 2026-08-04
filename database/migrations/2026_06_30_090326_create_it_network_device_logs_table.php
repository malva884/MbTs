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
        Schema::create('it_network_device_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('network_device_id');
            $table->enum('status', ['online', 'offline', 'unknown']);
            $table->integer('response_time_ms')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->foreign('network_device_id')->references('id')->on('it_network_devices')->onDelete('cascade');
            $table->index('network_device_id');
            $table->index('checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_network_device_logs');
    }
};
