<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('service_type_id');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('provider')->nullable();
            $table->string('server_url')->nullable();
            $table->string('domain')->nullable();
            $table->boolean('disabled')->default(false);
            $table->timestamps();

            $table->foreign('service_type_id')->references('id')->on('hr_service_types')->onDelete('cascade');
            $table->index('service_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_services');
    }
};
