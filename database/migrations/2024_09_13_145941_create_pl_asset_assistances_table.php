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
        Schema::create('pl_asset_assistances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->foreign('asset_id')->references('id')->on('pl_assets')->onDelete('cascade');
            $table->string('numero_segnalazione');
            $table->string('utente')->nullable();
            $table->text('task_id')->nullable();
            $table->text('motivazione')->nullable();
            $table->text('soluzione')->nullable();
            $table->integer('stato')->default(1);
            $table->bigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pl_asset_assistances');
    }
};
