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
        Schema::create('pl_asset_monitorings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->foreign('asset_id')->references('id')->on('pl_assets')->onDelete('cascade');
            $table->integer('id_client')->index();
            $table->dateTime('data');
            $table->string('tipo_log');
            $table->string('hostname')->nullable();
            $table->string('gp_stato')->nullable();
            $table->string('stl_app')->nullable();
            $table->string('portale_stato')->nullable();
            $table->string('dc_stato')->nullable();
            $table->string('ip_uno_stato')->nullable();
            $table->string('ip_due_stato')->nullable();
            $table->string('ip_tre_stato')->nullable();
            $table->string('ip_quatro_stato')->nullable();
            $table->string('ip_cinque_stato')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_asset_monitorings');
    }
};
