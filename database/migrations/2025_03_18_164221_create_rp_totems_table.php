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
        Schema::create('rp_totems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nome')->index();
            $table->string('ip_stampante')->nullable();
            $table->boolean('registrazione')->default(false);
            $table->boolean('informativa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_totems');
    }
};
