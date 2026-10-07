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
        Schema::create('wf_variations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ol')->index();
            $table->string('stato')->default('In-Approval')->index();
            $table->bigInteger('creator')->index();
            $table->longText('testo')->nullable();
            $table->integer('revisione')->default(0);
            $table->uuid('categoria_id')->nullable()->index();
            $table->date('data_approvazione')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->integer('tipologia')->nullable();
            $table->text('folder_drive')->nullable();
            $table->text('id_file_drive')->nullable();
            $table->text('id_log_drive')->nullable();
            $table->boolean('visibile')->default(true)->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_variations');
    }
};
