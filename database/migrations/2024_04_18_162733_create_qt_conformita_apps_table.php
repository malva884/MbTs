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
        Schema::create('qt_conformita_apps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conformitas_id')->index();
            $table->text('soluzione');
            $table->bigInteger('user_soluzione')->index();
            $table->text('nota_approvazione')->nullable();
            $table->bigInteger('user_approvazione')->nullable()->index();
            $table->dateTime('data_soluzione');
            $table->dateTime('data_approvazione')->nullable();
            $table->integer('esito')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_conformita_apps');
    }
};
