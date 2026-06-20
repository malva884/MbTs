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
        Schema::create('hr_approver_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('user_id')->index();
            $table->string('livello');
            $table->string('centro_ci_costo');
            $table->boolean('disattivo')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hr_approver_requests');
    }
};
