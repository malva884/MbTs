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
        Schema::create('pl_asset_map_locations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('map_id')->index();
            $table->foreign('map_id')->references('id')->on('pl_asset_maps')->onDelete('cascade');
            $table->uuid('asset_id')->index();
            $table->foreign('asset_id')->references('id')->on('pl_assets')->onDelete('cascade');
            $table->string('utente')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('numero_seriale')->nullable();
            $table->date('data_allocazione')->nullable();
            $table->string('posX');
            $table->string('posY');
            $table->string('cordinate');
            $table->string('tipo_asset');
            $table->bigInteger('user_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_asset_map_locations');
    }
};
