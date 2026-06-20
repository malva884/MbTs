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
        Schema::create('task_user_assigneds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('area_id')->index();
            $table->uuid('task_id')->index();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->bigInteger('user_id')->index();
            $table->boolean('responsible')->default(false);
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_user_assigneds');
    }
};
