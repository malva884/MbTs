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
        Schema::create('pr_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('materiale');
            $table->string('descrizione')->index();
            $table->decimal('quantita', $precision = 10, $scale = 3)->default(0.000);
            $table->decimal('importo', $precision = 10, $scale = 2)->default(0.00);
            $table->string('um');
            $table->string('lotto');
            $table->string('plant');
            $table->string('posizione_archiviazione');
            $table->string('tipo_movimento')->index();
            $table->string('special_stock');
            $table->string('documento_materiale');
            $table->date('data_pubblicazione')->index();
            $table->date('data_documento')->index();
            $table->date('data_inserimento')->index();
            $table->string('testo_movimento');
            $table->string('user')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pr_movements');
    }
};
