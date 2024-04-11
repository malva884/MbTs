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
        Schema::create('qt_categories', function (Blueprint $table) {
            $table->id();
            $table->string('categoria')->index();
            $table->text('descrizione')->nullable();
            $table->string('valore')->nullable();
            $table->boolean('disabled')->default(false);
            $table->integer('moduli')->index();
            $table->text('id_drive')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qt_categories');
    }
};
