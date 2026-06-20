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
        Schema::create('to_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero')->index();
            $table->string('rdo')->nullable();
            $table->string('parametro');
            $table->bigInteger('user')->index();
            $table->uuid('cliente_id')->index();
            $table->decimal('cu', $precision = 10, $scale = 2)->default(0.00);
            $table->date('data_rdo')->nullable();
            $table->date('data_preventivo');
            $table->string('nota')->nullable();
            $table->string('company_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('to_quotes');
    }
};
