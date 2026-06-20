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
        Schema::create('sy_users_folders_shareds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('folder_id')->index();
            $table->foreign('folder_id')->references('id')->on('sy_folder_shareds')->onDelete('cascade');
            $table->bigInteger('user')->index();
            $table->string('ruolo')->index();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sy_users_folders_shareds');
    }
};
