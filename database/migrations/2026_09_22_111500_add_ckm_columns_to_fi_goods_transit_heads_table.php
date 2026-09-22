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
        Schema::table('fi_goods_transit_heads', function (Blueprint $table) {
            $table->decimal('value_ckm', $precision = 10, $scale = 3)->default(0.000)->after('value_fkm');
            $table->decimal('value_cc_ckm', $precision = 10, $scale = 3)->default(0.000)->after('value_ckm');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fi_goods_transit_heads', function (Blueprint $table) {
            $table->dropColumn(['value_ckm', 'value_cc_ckm']);
        });
    }
};
