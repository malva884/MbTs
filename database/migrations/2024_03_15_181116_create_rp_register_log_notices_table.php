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
        Schema::create('rp_register_log_notices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('cod_riferimento')->index();
            $table->bigInteger('user')->index();
            $table->boolean('notifica')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_register_log_notices');
    }
};
