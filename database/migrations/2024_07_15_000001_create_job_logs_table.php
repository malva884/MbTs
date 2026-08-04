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
        Schema::create('job_logs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name');
            $table->string('job_type'); // 'queue' or 'cron'
            $table->string('status'); // 'pending', 'running', 'success', 'failed'
            $table->text('output')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->float('duration')->nullable(); // in seconds
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['job_name', 'created_at']);
            $table->index('status');
            $table->index('job_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_logs');
    }
};
