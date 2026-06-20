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
        Schema::create('sp_picking_list_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('picking_id')->index();
            $table->foreign('picking_id')->references('id')->on('sp_picking_lists')->onDelete('cascade');
            $table->string('ordine')->index();
            $table->string('lotto')->index();
            $table->string('materiale');
            $table->string('um');
            $table->decimal('giacenza', $precision = 10, $scale = 3)->default(0.000);
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sp_picking_list_batches');
    }
};
