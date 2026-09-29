<?php

namespace App\Http\Controllers;

use App\Models\DdtSpedizione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpDdtController extends Controller
{
    /**
     * Lista paginata dei DDT di spedizione con filtri (vettore, provincia, regione, date, costo).
     */
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy') ?: 'data_ddt';
        $orderBy = $request->get('orderBy') ?: 'desc';
        $vettore = $request->get('vettore');
        $provincia = $request->get('provincia');
        $regione = $request->get('regione');
        $numeroDdt = $request->get('numero_ddt');
        $dataDa = $request->get('data_da');
        $dataA = $request->get('data_a');
        $conCosto = $request->get('con_costo'); // 'si' | 'no' | null

        $objs = DdtSpedizione::query()
            ->where(function ($query) use ($vettore) {
                if ($vettore) {
                    $query->where('vettore', 'LIKE', '%' . $vettore . '%');
                }
            })
            ->where(function ($query) use ($provincia) {
                if ($provincia) {
                    $query->where('destinazione_provincia', 'LIKE', '%' . $provincia . '%');
                }
            })
            ->where(function ($query) use ($regione) {
                if ($regione) {
                    $query->where('destinazione_regione', 'LIKE', '%' . $regione . '%');
                }
            })
            ->where(function ($query) use ($numeroDdt) {
                if ($numeroDdt) {
                    $query->where('numero_ddt', 'LIKE', '%' . $numeroDdt . '%');
                }
            })
            ->where(function ($query) use ($dataDa) {
                if ($dataDa) {
                    $query->whereDate('data_ddt', '>=', $dataDa);
                }
            })
            ->where(function ($query) use ($dataA) {
                if ($dataA) {
                    $query->whereDate('data_ddt', '<=', $dataA);
                }
            })
            ->where(function ($query) use ($conCosto) {
                if ($conCosto === 'si') {
                    $query->whereNotNull('costo_spedizione');
                } elseif ($conCosto === 'no') {
                    $query->whereNull('costo_spedizione');
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->orderByDesc('created_at')
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    /**
     * Vettori distinti presenti nei DDT (per il filtro select).
     */
    public function vettori()
    {
        $vettori = DB::table('ddt_spedizioni')
            ->whereNotNull('vettore')
            ->where('vettore', '<>', '')
            ->distinct()
            ->orderBy('vettore')
            ->pluck('vettore');

        return response()->json($vettori);
    }

    /**
     * Riepilogo costi: totale, numero DDT con/senza costo.
     */
    public function stats(Request $request)
    {
        $stats = DB::table('ddt_spedizioni')
            ->selectRaw('COUNT(*) as totale')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NOT NULL THEN 1 ELSE 0 END) as con_costo')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NULL THEN 1 ELSE 0 END) as senza_costo')
            ->selectRaw('COALESCE(SUM(costo_spedizione), 0) as costo_totale')
            ->first();

        $perVettore = DB::table('ddt_spedizioni')
            ->select('vettore')
            ->selectRaw('COUNT(*) as numero_ddt')
            ->selectRaw('COALESCE(SUM(costo_spedizione), 0) as costo_totale')
            ->whereNotNull('vettore')
            ->where('vettore', '<>', '')
            ->groupBy('vettore')
            ->orderByDesc('costo_totale')
            ->get();

        return response()->json([
            'stats' => $stats,
            'per_vettore' => $perVettore,
        ]);
    }
}
