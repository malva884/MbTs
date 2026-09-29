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
        Schema::create('ddt_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable();
            $table->string('drive_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->uuid('wf_order_id')->nullable();
            $table->string('numero_ddt')->nullable()->index();
            $table->date('data_ddt')->nullable();
            $table->string('riferimento_interno')->nullable();
            $table->string('ns_ovd')->nullable()->index();
            $table->unsignedInteger('n_colli')->nullable();
            $table->decimal('peso_lordo_kg', 10, 3)->nullable();
            $table->decimal('peso_netto_kg', 10, 3)->nullable();
            $table->string('vettore')->nullable();
            $table->boolean('vettore_palletways')->default(false);
            $table->boolean('vettore_susa')->default(false);
            $table->string('destinazione_nome')->nullable();
            $table->string('destinazione_indirizzo')->nullable();
            $table->string('status')->default('processed')->index();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ddt_spedizioni');
    }
};
