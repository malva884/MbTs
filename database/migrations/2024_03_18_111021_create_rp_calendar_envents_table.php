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
        Schema::create('rp_calendar_envents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('evento_id')->index();
            $table->string('titolo')->index();
            $table->dateTime('data_inizio')->index();
            $table->dateTime('data_fine')->nullable();
            $table->boolean('eliminato')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_calendar_envents');
    }
};
