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
        Schema::create('it_assets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->index();
            $table->uuid('location_id')->nullable()->index();
            $table->string('serial_number')->nullable()->unique();
            $table->string('asset_tag')->nullable()->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchase_date')->nullable();
            $table->string('product_link')->nullable();
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->integer('quantity')->default(1);
            $table->enum('status', ['Available', 'Assigned', 'In Repair', 'Retired', 'Lost'])->default('Available')->index();
            $table->text('notes')->nullable();
            $table->boolean('disabled')->default(false)->index();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('it_categories');
            $table->foreign('location_id')->references('id')->on('it_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_assets');
    }
};
