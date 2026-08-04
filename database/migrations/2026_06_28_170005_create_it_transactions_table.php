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
        Schema::create('it_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('asset_id')->index();
            $table->enum('type', ['In', 'Out', 'Transfer', 'Maintenance', 'Return', 'Retire'])->default('In')->index();
            $table->uuid('from_location_id')->nullable()->index();
            $table->uuid('to_location_id')->nullable()->index();
            $table->bigInteger('performed_by')->unsigned()->index();
            $table->timestamp('date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('asset_id')->references('id')->on('it_assets');
            $table->foreign('from_location_id')->references('id')->on('it_locations')->onDelete('no action');
            $table->foreign('to_location_id')->references('id')->on('it_locations')->onDelete('no action');
            $table->foreign('performed_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_transactions');
    }
};
