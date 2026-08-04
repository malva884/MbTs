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
        Schema::create('it_network_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->string('ip_address')->nullable();
            $table->string('mac_address')->nullable();
            $table->enum('device_type', ['Router', 'Switch', 'Access Point', 'Server', 'Firewall', 'Other'])->default('Other');
            $table->string('location')->nullable();
            $table->string('rack_position')->nullable();
            $table->string('vlan')->nullable();
            $table->string('subnet')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('disabled')->default(false)->index();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('it_assets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_network_devices');
    }
};
