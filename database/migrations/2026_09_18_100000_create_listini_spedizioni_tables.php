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
        Schema::create('listini_spedizioni', function (Blueprint $table) {
            $table->id();
            $table->string('vettore', 100);
            $table->string('descrizione')->nullable();
            $table->string('tipo', 20); // 'pallet' | 'peso'
            $table->unsignedSmallInteger('anno')->nullable();
            $table->date('valido_da')->nullable();
            $table->date('valido_a')->nullable();
            $table->string('file_name')->nullable();
            $table->string('status', 20)->default('processing'); // processing | processed | error
            $table->text('error_message')->nullable();
            $table->boolean('attivo')->default(true);
            $table->json('raw_response')->nullable();
            $table->timestamps();
        });

        Schema::create('listini_spedizioni_voci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listino_id')->constrained('listini_spedizioni')->cascadeOnDelete();
            $table->string('regione', 100)->nullable();
            $table->string('provincia', 10)->nullable();
            $table->string('hub', 10)->nullable();
            $table->string('servizio', 20)->nullable(); // PREMIUM | ECONOMY
            $table->string('fascia', 20)->nullable(); // FP|LP|ULP|HP|ELP|QP|MQ oppure kg_100|kg_500|kg_1000|kg_over
            $table->decimal('peso_da', 10, 2)->nullable();
            $table->decimal('peso_a', 10, 2)->nullable();
            $table->decimal('prezzo', 10, 3);
            $table->string('tipo_voce', 20)->default('tariffa'); // tariffa | inoltro
            $table->timestamps();

            $table->index(['listino_id', 'provincia']);
            $table->index(['listino_id', 'regione']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listini_spedizioni_voci');
        Schema::dropIfExists('listini_spedizioni');
    }
};
