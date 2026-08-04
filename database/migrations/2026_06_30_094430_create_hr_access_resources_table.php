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
        Schema::create('hr_access_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('access_type_id');
            $table->string('name');
            $table->string('path')->nullable();
            $table->string('description')->nullable();
            $table->boolean('disabled')->default(false);
            $table->timestamps();

            $table->foreign('access_type_id')->references('id')->on('hr_access_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_access_resources');
    }
};
