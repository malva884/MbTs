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
        Schema::create('wf_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('categoria');
            $table->string('model');
            $table->text('folder_drive')->nullable();
            $table->string('descrizione')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_categories');
    }
};
