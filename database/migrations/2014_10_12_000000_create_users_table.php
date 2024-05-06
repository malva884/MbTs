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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nome');
            $table->string('cognome');
            $table->string('full_name');
            $table->string('sesso');
            $table->string('mobile')->nullable();
            $table->string('interno')->nullable();
            $table->string('lingua')->nullable();
            $table->integer('stato')->default(1)->index();
            $table->boolean('_deleted')->default(false)->index();
            $table->string('avatar')->nullable();
            $table->string('img_signature')->nullable();
            $table->string('workflow')->nullable();
            $table->string('role');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->index();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
