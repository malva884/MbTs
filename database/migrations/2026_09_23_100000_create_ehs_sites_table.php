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
        Schema::create('ehs_sites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('site');
            $table->boolean('disattivo')->default(false);
            $table->unsignedBigInteger('old_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ehs_sites');
    }
};
