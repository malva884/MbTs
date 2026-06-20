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
        Schema::create('wf_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('commessa');
            $table->string('commessa_sistema');
            $table->string('stato');
            $table->string('revisione')->nullable();
            $table->uuid('id_commessa_padre')->nullable();
            $table->date('data_approvazione');
            $table->integer('tipologia');
            $table->uuid('categoria_id')->index();
            $table->bigInteger('creator');
            $table->text('folder_drive')->nullable();
            $table->text('id_file_drive')->nullable();
            $table->text('id_log_drive')->nullable();
            $table->boolean('visibile')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_orders');
    }
};
