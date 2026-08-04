<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_asset_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->integer('min_stock')->default(0);
            $table->timestamps();

            $table->unique(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_asset_groups');
    }
};
