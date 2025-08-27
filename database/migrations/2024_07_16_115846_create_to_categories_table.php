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
        Schema::create('to_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('categoria');
            $table->integer('modulo')->index();
            $table->string('legistrazione')->nullable();
            $table->text('nota')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_categories');
    }
};
