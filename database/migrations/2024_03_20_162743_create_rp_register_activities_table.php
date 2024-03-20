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
        Schema::create('rp_register_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rp_register_id')->index();
            $table->string('cod_riferimento')->index();
            $table->string('azione');
            $table->dateTime('data_azione');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rp_register_activities');
    }
};
