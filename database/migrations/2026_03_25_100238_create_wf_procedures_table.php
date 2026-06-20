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
        Schema::create('wf_procedures', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('procedura');
            $table->string('descrizione')->nullable();
            $table->string('revisione');
            $table->string('revisione_anno');
            $table->uuid('processo_id')->index();
            $table->uuid('categoria_id')->index();
            $table->bigInteger('user_id')->index();
            $table->string('stato');
            $table->date('data_approvazione')->nullable();
            $table->integer('tipologia')->index();
            $table->uuid('padre_id')->nullable();
            $table->boolean('sup')->nullable();
            $table->text('folder_drive_padre')->nullable();
            $table->text('folder_drive')->nullable();
            $table->text('id_file_drive')->nullable();
            $table->text('id_log_drive')->nullable();
            $table->boolean('notification')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_procedures');
    }
};
