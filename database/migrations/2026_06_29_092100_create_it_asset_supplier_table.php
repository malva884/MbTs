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
        Schema::create('it_asset_supplier', function (Blueprint $table) {
            $table->uuid('asset_id');
            $table->uuid('supplier_id');
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('order_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->primary(['asset_id', 'supplier_id']);
            $table->foreign('asset_id')->references('id')->on('it_assets')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('it_suppliers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_asset_supplier');
    }
};
