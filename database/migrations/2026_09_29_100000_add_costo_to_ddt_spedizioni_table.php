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
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->string('destinazione_provincia', 10)->nullable()->after('destinazione_indirizzo')->index();
            $table->string('destinazione_regione', 100)->nullable()->after('destinazione_provincia');
            $table->foreignId('listino_id')->nullable()->after('destinazione_regione')->constrained('listini_spedizioni')->nullOnDelete();
            $table->decimal('costo_spedizione', 10, 2)->nullable()->after('listino_id');
            $table->string('costo_tipo_calcolo', 20)->nullable()->after('costo_spedizione'); // peso | pallet | manuale
            $table->text('costo_note')->nullable()->after('costo_tipo_calcolo');
            $table->json('costo_dettaglio')->nullable()->after('costo_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ddt_spedizioni', function (Blueprint $table) {
            $table->dropForeign(['listino_id']);
            $table->dropColumn([
                'destinazione_provincia',
                'destinazione_regione',
                'listino_id',
                'costo_spedizione',
                'costo_tipo_calcolo',
                'costo_note',
                'costo_dettaglio',
            ]);
        });
    }
};
