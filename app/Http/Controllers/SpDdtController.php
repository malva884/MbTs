<?php

namespace App\Http\Controllers;

use App\Models\DdtSpedizione;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpDdtController extends Controller
{
    /**
     * Lista paginata dei DDT di spedizione con filtri (vettore, provincia, regione, date, costo).
     */
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy') ?: 'data_ddt';
        $orderBy = $request->get('orderBy') ?: 'desc';

        $objs = $this->applyFiltri(DdtSpedizione::query(), $request)
            ->orderBy($sortByName, $orderBy)
            ->orderByDesc('created_at')
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    /**
     * Applica i filtri comuni della lista DDT a una query (Eloquent o DB builder).
     */
    protected function applyFiltri($query, Request $request)
    {
        $vettore = $request->get('vettore');
        $provincia = $request->get('provincia');
        $regione = $request->get('regione');
        $numeroDdt = $request->get('numero_ddt');
        $nsOvd = $request->get('ns_ovd');
        $dataDa = $request->get('data_da');
        $dataA = $request->get('data_a');
        $conCosto = $request->get('con_costo'); // 'si' | 'no' | null

        return $query
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
            ->where(function ($query) use ($nsOvd) {
                if ($nsOvd) {
                    $query->where('ns_ovd', 'LIKE', '%' . $nsOvd . '%');
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
            });
    }

    /**
     * Vettori distinti presenti nei DDT (per il filtro select).
     */
    public function vettori()
    {
        $vettori = DB::table('ddt_spedizioni')
            ->whereNotNull('vettore')
            ->where('vettore', '<>', '')
            ->whereNotIn('vettore', ['Vettore', 'vettore', 'VETTORE', 'Mittente', 'mittente', 'MITTENTE', 'Destinatario', 'destinatario', 'DESTINATARIO'])
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
        $stats = $this->applyFiltri(DB::table('ddt_spedizioni'), $request)
            ->selectRaw('COUNT(*) as totale')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NOT NULL THEN 1 ELSE 0 END) as con_costo')
            ->selectRaw('SUM(CASE WHEN costo_spedizione IS NULL THEN 1 ELSE 0 END) as senza_costo')
            ->selectRaw('COALESCE(SUM(costo_spedizione), 0) as costo_totale')
            ->first();

        $perVettore = $this->applyFiltri(DB::table('ddt_spedizioni'), $request)
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

    /**
     * Restituisce il PDF del DDT in anteprima inline, scaricandolo da Google Drive.
     */
    public function preview(string $id)
    {
        $ddt = DdtSpedizione::findOrFail($id);
        $content = null;

        if ($ddt->pdf_path) {
            if (str_contains($ddt->pdf_path, '/')) {
                // Path sul disco ddt_spedizioni_drive (es. Bolle/pending_workflow/xxx.pdf)
                $content = Storage::disk('ddt_spedizioni_drive')->get($ddt->pdf_path);
            } else {
                // File ID Drive: il PDF è nella cartella della commessa
                $content = GoogleDrive::download($ddt->pdf_path);
            }
        } elseif ($ddt->file_name) {
            // Record non riconosciuto: il file originale è in Bolle/errori/
            $disk = Storage::disk('ddt_spedizioni_drive');
            $base = pathinfo($ddt->file_name, PATHINFO_FILENAME);

            foreach ($disk->files('Bolle/errori') as $file) {
                $name = pathinfo($file, PATHINFO_FILENAME);
                if ($name === $base || str_starts_with($name, $base . '_')) {
                    $content = $disk->get($file);
                    break;
                }
            }
        }

        if (empty($content)) {
            return response()->json(['message' => 'PDF non trovato su Drive'], 404);
        }

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($ddt->pdf_path ?? $ddt->file_name ?? 'ddt.pdf') . '"',
        ]);
    }
}
