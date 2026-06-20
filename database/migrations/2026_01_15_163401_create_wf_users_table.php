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
        Schema::create('wf_users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->uuid('role_id')->index();
            $table->string('model');
            $table->date('approval_start_date')->nullable();
            $table->boolean('disabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_users');
    }
};
