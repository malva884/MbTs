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
        Schema::create('wf_user_approvals', function (Blueprint $table) {
            $table->bigInteger('user_id')->index();
            $table->uuid('role_id')->index();
            $table->uuid('model_id')->index();
            $table->string('model');
            $table->string('approval_action', 12)->default('Approved');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_user_approvals');
    }
};
