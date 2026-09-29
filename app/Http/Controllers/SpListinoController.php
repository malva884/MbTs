<?php

namespace App\Http\Controllers;

use App\Jobs\ImportListinoSpedizione;
use App\Models\ListinoSpedizione;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SpListinoController extends Controller
{
    /**
     * Lista paginata dei listini con filtri.
     */
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy') ?: 'created_at';
        $orderBy = $request->get('orderBy') ?: 'desc';
        $vettore = $request->get('vettore');
        $tipo = $request->get('tipo');
        $attivo = $request->get('attivo');

        $objs = DB::table('listini_spedizioni')
            ->select('listini_spedizioni.*')
            ->selectRaw('(SELECT COUNT(*) FROM listini_spedizioni_voci WHERE listino_id = listini_spedizioni.id) as numero_voci')
            ->where(function ($query) use ($vettore) {
                if ($vettore) {
                    $query->where('vettore', 'LIKE', '%' . $vettore . '%');
                }
            })
            ->where(function ($query) use ($tipo) {
                if ($tipo) {
                    $query->where('tipo', $tipo);
                }
            })
            ->where(function ($query) use ($attivo) {
                if ($attivo !== null && $attivo !== '') {
                    $query->where('attivo', filter_var($attivo, FILTER_VALIDATE_BOOLEAN));
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    /**
     * Upload del PDF del listino: salva il file, crea la testata e dispatcha il job di import.
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
            'vettore' => 'required|string|max:100',
            'tipo' => 'required|in:pallet,peso',
            'descrizione' => 'nullable|string|max:255',
            'anno' => 'nullable|integer|min:2000|max:2100',
        ]);

        try {
            $file = $request->file('file');
            $nomeFile = $file->getClientOriginalName();
            $percorso = $file->storeAs('listini_spedizioni', uniqid() . '_' . $nomeFile, 'local');
            $percorsoAssoluto = storage_path('app/' . $percorso);

            $listino = ListinoSpedizione::create([
                'vettore' => strtoupper(trim($request->vettore)),
                'descrizione' => $request->descrizione,
                'tipo' => $request->tipo,
                'anno' => $request->anno,
                'file_name' => $nomeFile,
                'status' => ListinoSpedizione::STATUS_PROCESSING,
                'attivo' => true,
            ]);

            ImportListinoSpedizione::dispatch($listino->id, $percorsoAssoluto);

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Listino-Caricato',
                'color' => 'success',
                'obj' => $listino,
            ]);
        } catch (\Exception $e) {
            Log::error('[SpListinoController] Errore upload listino: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Errore-Upload',
                'color' => 'error',
            ], 500);
        }
    }

    /**
     * Dettaglio di un listino con le voci paginate.
     */
    public function voci(Request $request, $id)
    {
        $sortByName = $request->get('sortBy') ?: 'regione';
        $orderBy = $request->get('orderBy') ?: 'asc';
        $regione = $request->get('regione');
        $provincia = $request->get('provincia');
        $servizio = $request->get('servizio');
        $fascia = $request->get('fascia');

        $objs = DB::table('listini_spedizioni_voci')
            ->where('listino_id', $id)
            ->where(function ($query) use ($regione) {
                if ($regione) {
                    $query->where('regione', 'LIKE', '%' . $regione . '%');
                }
            })
            ->where(function ($query) use ($provincia) {
                if ($provincia) {
                    $query->where('provincia', 'LIKE', '%' . $provincia . '%');
                }
            })
            ->where(function ($query) use ($servizio) {
                if ($servizio) {
                    $query->where('servizio', $servizio);
                }
            })
            ->where(function ($query) use ($fascia) {
                if ($fascia) {
                    $query->where('fascia', $fascia);
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->orderBy('provincia')
            ->orderBy('servizio')
            ->orderBy('fascia')
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    /**
     * Aggiorna i dati della testata (descrizione, anno, attivo).
     */
    public function update(Request $request, $id)
    {
        $obj = ListinoSpedizione::find($id);

        if (!$obj) {
            return response()->json(['success' => false, 'message' => 'Messaggi.Listino-Non-Trovato', 'color' => 'error'], 404);
        }

        $obj->descrizione = $request->descrizione;
        $obj->anno = $request->anno;
        $obj->attivo = $request->attivo ? true : false;
        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Listino-Aggiornato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Elimina un listino e le sue voci (cascade).
     */
    public function deleted($id)
    {
        $obj = ListinoSpedizione::find($id);

        if (!$obj) {
            return response()->json(['success' => false, 'message' => 'Messaggi.Listino-Non-Trovato', 'color' => 'error'], 404);
        }

        $obj->delete();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Listino-Eliminato',
            'color' => 'success',
        ]);
    }
}
