<?php

namespace App\Http\Controllers;

use App\Models\EhsAnatomical;
use App\Models\EhsCause;
use App\Models\EhsEvent;
use App\Models\EhsInjury;
use App\Models\EhsSite;
use App\Models\EhsTypeEvent;
use App\Models\HrEmployee;
use App\Services\GoogleDrive;
use App\Services\SettingService;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EhsEventController extends Controller
{
    /**
     * Lista paginata degli eventi EHS con filtri.
     */
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy') ?: 'data_evento';
        $orderBy = $request->get('orderBy') ?: 'desc';
        $tipoScheda = $request->get('tipo_scheda');
        $tipoRilevazione = $request->get('tipo_rilevazione');
        $dataDa = $request->get('data_da');
        $dataA = $request->get('data_a');
        $matricola = $request->get('matricola');
        $dipendente = $request->get('dipendente');

        $query = EhsEvent::query()
            ->with(['employee:id,nome,cognome,matricola', 'reparto:id,reparto', 'impianto:id,site', 'causa:id,causa'])
            ->where(function ($q) use ($tipoScheda) {
                if ($tipoScheda !== null && $tipoScheda !== '')
                    $q->where('tipo_scheda', $tipoScheda);
            })
            ->where(function ($q) use ($tipoRilevazione) {
                if ($tipoRilevazione)
                    $q->where('tipo_rilevazione', $tipoRilevazione);
            })
            ->where(function ($q) use ($dataDa, $dataA) {
                if ($dataDa && $dataA)
                    $q->whereBetween('data_evento', [$dataDa . ' 00:00:00', $dataA . ' 23:59:59']);
                elseif ($dataDa)
                    $q->where('data_evento', '>=', $dataDa . ' 00:00:00');
                elseif ($dataA)
                    $q->where('data_evento', '<=', $dataA . ' 23:59:59');
            })
            ->where(function ($q) use ($matricola) {
                if ($matricola)
                    $q->where('matricola', 'like', '%' . $matricola . '%');
            })
            ->where(function ($q) use ($dipendente) {
                if ($dipendente) {
                    $q->where('nome', 'like', '%' . $dipendente . '%')
                        ->orWhere('cognome', 'like', '%' . $dipendente . '%')
                        ->orWhereHas('employee', function ($eq) use ($dipendente) {
                            $eq->where('nome_completo', 'like', '%' . $dipendente . '%');
                        });
                }
            });

        $objs = $query->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    /**
     * Dati per il form di creazione/modifica (tabelle di supporto).
     */
    public function formData()
    {
        return response()->json([
            'sedi' => EhsSite::where('disattivo', false)->orderBy('site')->get(['id', 'site']),
            'reparti' => DB::table('hr_departments')->where('disattivo', false)->orderBy('reparto')->get(['id', 'reparto']),
            'lesioni' => EhsInjury::where('disattivo', false)->orderBy('injurie')->get(['id', 'injurie']),
            's_anatomiche' => EhsAnatomical::where('disattivo', false)->orderBy('anatomical')->get(['id', 'anatomical']),
            't_eventi' => EhsTypeEvent::where('disattivo', false)->orderBy('event')->get(['id', 'event']),
            'cause' => EhsCause::where('disattivo', false)->orderBy('causa')->get(['id', 'causa']),
        ]);
    }

    /**
     * Creazione nuovo evento EHS.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tipo_scheda' => 'required|in:1,2',
            'tipo_rilevazione' => 'required|in:1,2,3,4,5',
            'data_evento' => 'required|date',
            'matricola' => 'nullable|string|max:50',
            'doc' => 'nullable|file|max:20480',
        ]);

        $obj = new EhsEvent();
        $this->fillEvent($obj, $request);
        $obj->user_create = Auth::id();

        // Collega il dipendente HR tramite matricola
        $this->linkEmployee($obj, $request);

        // Upload allegato su Google Drive
        if ($request->hasFile('doc')) {
            $obj->path_drive = $this->uploadToDrive($obj, $request->file('doc'));
        }

        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ehs-Evento-Creato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Dettaglio evento con relazioni.
     */
    public function view($id)
    {
        $obj = EhsEvent::with([
            'employee:id,nome,cognome,nome_completo,matricola',
            'reparto:id,reparto',
            'impianto:id,site',
            'lesione:id,injurie',
            'anatomica:id,anatomical',
            'evento:id,event',
            'causa:id,causa',
            'user:id,full_name',
        ])->findOrFail($id);

        return response()->json($obj);
    }

    /**
     * Aggiornamento evento.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tipo_rilevazione' => 'required|in:1,2,3,4,5',
            'data_evento' => 'required|date',
            'doc' => 'nullable|file|max:20480',
        ]);

        $obj = EhsEvent::findOrFail($id);
        $this->fillEvent($obj, $request);
        $this->linkEmployee($obj, $request);

        if ($request->hasFile('doc')) {
            $obj->path_drive = $this->uploadToDrive($obj, $request->file('doc'));
        }

        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ehs-Evento-Modificato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Eliminazione evento.
     */
    public function destroy($id)
    {
        $obj = EhsEvent::find($id);
        if (!$obj) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Ehs-Evento-Non-Trovato',
                'color' => 'error',
            ], 404);
        }

        $obj->delete();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Ehs-Evento-Eliminato',
            'color' => 'success',
        ]);
    }

    /**
     * Upload di un allegato su Google Drive per un evento esistente.
     */
    public function addFile(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid',
            'doc' => 'required|file|max:20480',
        ]);

        $obj = EhsEvent::findOrFail($request->id);
        $fileId = $this->uploadToDrive($obj, $request->file('doc'));

        if ($fileId) {
            $obj->path_drive = $fileId;
            $obj->save();
        }

        return response()->json([
            'success' => (bool) $fileId,
            'message' => $fileId ? 'Messaggi.Ehs-File-Caricato' : 'Messaggi.Ehs-File-Errore',
            'color' => $fileId ? 'success' : 'error',
            'file_id' => $fileId,
        ]);
    }

    /**
     * Dati aggregati per i grafici della dashboard.
     */
    public function stats(Request $request)
    {
        $tipoScheda = $request->get('tipo_scheda', 1);
        $dataDa = $request->get('data_da');
        $dataA = $request->get('data_a');

        $baseQuery = function () use ($tipoScheda, $dataDa, $dataA) {
            return EhsEvent::query()
                ->where('tipo_scheda', $tipoScheda)
                ->where(function ($q) use ($dataDa, $dataA) {
                    if ($dataDa && $dataA)
                        $q->whereBetween('data_evento', [$dataDa . ' 00:00:00', $dataA . ' 23:59:59']);
                    elseif ($dataDa)
                        $q->where('data_evento', '>=', $dataDa . ' 00:00:00');
                    elseif ($dataA)
                        $q->where('data_evento', '<=', $dataA . ' 23:59:59');
                });
        };

        $return = [];

        if ($tipoScheda == 1) {
            // Distribuzione per sede anatomica
            $return['lesioni'] = $this->countByRelation($baseQuery(), 's_anatomica_id', EhsAnatomical::class, 'anatomical');
            // Distribuzione per tipo lesione
            $return['tipolesioni'] = $this->countByRelation($baseQuery(), 'lesione_id', EhsInjury::class, 'injurie');
            // Distribuzione per causa
            $return['dinamica'] = $this->countByRelation($baseQuery(), 'causa_id', EhsCause::class, 'causa');
        } else {
            // Distribuzione per tipo evento
            $return['eventi'] = $this->countByRelation($baseQuery(), 'tipo_evento_id', EhsTypeEvent::class, 'event');
        }

        // Trend annuale (ultimi 10 anni)
        $year = date('Y') - 9;
        $yearly = (clone $baseQuery())
            ->select(DB::raw('YEAR(data_evento) as anno'), DB::raw('COUNT(*) as totale'), DB::raw('SUM(giorni_infortunio) as giorni'))
            ->whereYear('data_evento', '>=', $year)
            ->groupBy(DB::raw('YEAR(data_evento)'))
            ->orderBy(DB::raw('YEAR(data_evento)'))
            ->get()
            ->keyBy('anno');

        $yearData = [];
        $mediaData = [];
        $yearCats = [];
        for ($y = $year; $y <= date('Y'); $y++) {
            $row = $yearly->get($y);
            $totale = $row->totale ?? 0;
            $giorni = $row->giorni ?? 0;
            $yearData[] = $totale;
            $mediaData[] = $totale > 0 ? round($giorni / $totale, 2) : 0;
            $yearCats[] = $y;
        }

        $return['year'] = ['data' => $yearData, 'categories' => $yearCats];
        $return['mediaInf'] = ['data' => $mediaData, 'categories' => $yearCats];

        return response()->json($return);
    }

    /**
     * Export PDF della scheda evento.
     */
    public function export($id)
    {
        $obj = EhsEvent::with(['employee', 'reparto', 'impianto', 'lesione', 'anatomica', 'evento', 'causa', 'user'])
            ->findOrFail($id);

        $infortunio = [
            'created_at' => $obj->created_at,
            'nome' => $obj->employee ? $obj->employee->nome_completo : trim($obj->nome . ' ' . $obj->cognome),
            'matricola' => $obj->matricola,
            'user' => $obj->user->full_name ?? '-',
            'data_evento' => $obj->data_evento,
            'giorni_infortunio' => $obj->giorni_infortunio,
            'qualifica' => $this->qualificaLabel($obj->qualifica),
            'causa' => $obj->causa->causa ?? '-',
            'department' => $obj->reparto->reparto ?? '-',
            'site' => $obj->impianto->site ?? '-',
            'tipo_scheda' => $obj->tipo_scheda,
            'testimoni' => $obj->testimoni,
            'desc_dinamica' => $obj->desc_dinamica,
            'azioni' => $obj->azioni,
            'analisi_causa' => $obj->analisi_causa,
            'azioni_contenimento' => $obj->azioni_contenimento,
            'responsabile_azione' => $obj->responsabile_azione,
            'ora_lavorativa' => $obj->ora_lavorativa,
            't_tipo_inf' => $this->tipoRilevazioneLabel($obj->tipo_rilevazione),
        ];

        if ($obj->tipo_scheda == 1) {
            $infortunio['injurie'] = $obj->lesione->injurie ?? '-';
            $infortunio['anatomical'] = $obj->anatomica->anatomical ?? '-';
        } else {
            $infortunio['event'] = $obj->evento->event ?? '-';
        }

        $data = [
            'infortunio' => $infortunio,
            'logo' => public_path('/images/custom/logo_mb.png'),
        ];

        $pdf = PDF::loadView('ehs.pdf.scheda', compact('data'))->setPaper('a4', 'landscape');

        return $pdf->download('scheda_ehs_' . $obj->id . '.pdf');
    }

    /**
     * Importa i dati storici dal vecchio gestionale (mysql_old).
     */
    public function import()
    {
        set_time_limit(300);

        $stats = [
            'sites' => 0, 'causes' => 0, 'injuries' => 0,
            'anatomicals' => 0, 'type_events' => 0, 'departments' => 0, 'events' => 0,
            'events_skipped' => 0, 'events_updated' => 0, 'employees_not_found' => [],
        ];

        try {
            // 1. Tabelle di supporto
            $stats['sites'] = $this->importLookup('sites', EhsSite::class, 'site');
            $stats['causes'] = $this->importLookup('causes', EhsCause::class, 'causa');
            $stats['injuries'] = $this->importLookup('injuries', EhsInjury::class, 'injurie');
            $stats['anatomicals'] = $this->importLookup('anatomicals', EhsAnatomical::class, 'anatomical');
            $stats['type_events'] = $this->importLookup('type__events', EhsTypeEvent::class, 'event');

            // 2. Mappe old_id -> uuid
            $siteMap = EhsSite::whereNotNull('old_id')->pluck('id', 'old_id');
            $causeMap = EhsCause::whereNotNull('old_id')->pluck('id', 'old_id');
            $injuryMap = EhsInjury::whereNotNull('old_id')->pluck('id', 'old_id');
            $anatomicalMap = EhsAnatomical::whereNotNull('old_id')->pluck('id', 'old_id');
            $typeEventMap = EhsTypeEvent::whereNotNull('old_id')->pluck('id', 'old_id');

            // Reparti: sincronizza i vecchi departments in hr_departments (mappa old_id => uuid)
            $deptMap = $this->importDepartments($stats);

            // 3. Eventi
            $oldEvents = DB::connection('mysql_old')->table('scheda__infortunios')->get();

            foreach ($oldEvents as $old) {
                // Se già importato, backfill dei campi mancanti/non normalizzati
                $existing = EhsEvent::where('old_id', $old->id)->first();
                if ($existing) {
                    $dirty = false;
                    if (!$existing->reparto_id && isset($deptMap[$old->reparto_id])) {
                        $existing->reparto_id = $deptMap[$old->reparto_id];
                        $dirty = true;
                    }
                    $ora = $this->normalizeOraLavorativa($old->ora_lavorativa);
                    if ($existing->ora_lavorativa !== $ora) {
                        $existing->ora_lavorativa = $ora;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $existing->save();
                        $stats['events_updated']++;
                    }
                    $stats['events_skipped']++;
                    continue;
                }

                $event = new EhsEvent();
                $event->old_id = $old->id;
                $event->tipo_scheda = $old->tipo_scheda;
                $event->tipo_rilevazione = $old->tipo_rilevazione;
                $event->nome = $old->nome;
                $event->cognome = $old->cognome;
                $event->matricola = $old->matricola;
                $event->qualifica = $old->qualifica;
                $event->testimoni = $old->testimoni;
                $event->data_evento = $old->data_evento;
                $event->ora_lavorativa = $this->normalizeOraLavorativa($old->ora_lavorativa);
                $event->desc_dinamica = $old->desc_dinamica;
                $event->azioni = $old->azioni;
                $event->giorni_infortunio = $old->giorni_infortunio;
                $event->analisi_causa = $old->analisi_causa;
                $event->azioni_contenimento = $old->azioni_contenimento;
                $event->responsabile_azione = $old->responsabile_azione;
                $event->data_chiusura = $old->data_chiusura;
                $event->created_at = $old->created_at;

                // Mappa FK verso nuove tabelle
                $event->sede_id = $siteMap->get($old->sede_id);
                $event->causa_id = $causeMap->get($old->cause);
                $event->lesione_id = $injuryMap->get($old->lesione_id);
                $event->s_anatomica_id = $anatomicalMap->get($old->s_anatomica_id);
                $event->tipo_evento_id = $typeEventMap->get($old->tipo_evento);

                // Reparto: mappa diretta old departments.id => hr_departments.id
                $event->reparto_id = $deptMap[$old->reparto_id] ?? null;

                // Dipendente: match per matricola
                if ($old->matricola) {
                    $employee = HrEmployee::where('matricola', $old->matricola)->first();
                    if ($employee) {
                        $event->employee_id = $employee->id;
                    } else {
                        $stats['employees_not_found'][] = $old->matricola;
                    }
                }

                $event->save();
                $stats['events']++;
            }

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Ehs-Import-Completato',
                'color' => 'success',
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            Log::error('EHS import error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Ehs-Import-Errore',
                'color' => 'error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // -------------------------------------------------------------------------
    // Metodi privati
    // -------------------------------------------------------------------------

    /**
     * Popola i campi comuni dell'evento dalla request.
     */
    private function fillEvent(EhsEvent $obj, Request $request): void
    {
        $obj->tipo_scheda = $request->tipo_scheda ?? $obj->tipo_scheda;
        $obj->tipo_rilevazione = $request->tipo_rilevazione;
        $obj->matricola = $request->matricola;
        $obj->nome = $request->nome ? ucwords(strtolower($request->nome)) : null;
        $obj->cognome = $request->cognome ? ucwords(strtolower($request->cognome)) : null;
        $obj->qualifica = $request->qualifica;
        $obj->testimoni = $request->testimoni;
        $obj->data_evento = $request->data_evento;
        $obj->ora_lavorativa = $request->ora_lavorativa;
        $obj->desc_dinamica = $request->desc_dinamica;
        $obj->causa_id = $request->causa_id;
        $obj->azioni = $request->azioni;
        $obj->reparto_id = $request->reparto_id;
        $obj->sede_id = $request->sede_id;
        $obj->giorni_infortunio = $request->giorni_infortunio;
        $obj->analisi_causa = $request->analisi_causa;
        $obj->azioni_contenimento = $request->azioni_contenimento;
        $obj->responsabile_azione = $request->responsabile_azione;
        $obj->data_chiusura = $request->data_chiusura ?: null;

        if ($obj->tipo_scheda == 1) {
            $obj->lesione_id = $request->lesione_id;
            $obj->s_anatomica_id = $request->s_anatomica_id;
            $obj->tipo_evento_id = null;
        } else {
            $obj->tipo_evento_id = $request->tipo_evento_id;
            $obj->lesione_id = null;
            $obj->s_anatomica_id = null;
        }
    }

    /**
     * Collega l'evento a un dipendente HR tramite matricola.
     */
    private function linkEmployee(EhsEvent $obj, Request $request): void
    {
        if ($request->employee_id) {
            $obj->employee_id = $request->employee_id;
            return;
        }

        if ($request->matricola) {
            $employee = HrEmployee::where('matricola', $request->matricola)->first();
            if ($employee) {
                $obj->employee_id = $employee->id;
                $obj->nome = $employee->nome;
                $obj->cognome = $employee->cognome;
            }
        }
    }

    /**
     * Carica un file su Google Drive nella cartella corretta.
     */
    private function uploadToDrive(EhsEvent $obj, $file): ?string
    {
        try {
            $settingService = new SettingService();
            $year = $obj->data_evento ? $obj->data_evento->format('Y') : date('Y');

            $rootId = $obj->tipo_scheda == 1
                ? $settingService->get('google_drive_ehs_infortuni_folder_id', '1kwcicmjVhz599BM5tc-Dqtjxf4EBKcEz')
                : $settingService->get('google_drive_ehs_eventi_folder_id', '19ePIU3K6k0ZGwUOOEVqUCTyn-Ebjf7Cl');

            // Riutilizza la cartella dell'evento se già creata (upload successivi),
            // altrimenti crea root/<anno>/<data_evento>
            $folder = $obj->path_drive;
            if (!$folder) {
                $nome = trim(($obj->nome ?? '') . ' ' . ($obj->cognome ?? ''));
                $folderName = ($nome ? str_replace("'", ' ', $nome) . ' ' : '')
                    . ($obj->data_evento ? $obj->data_evento->format('d-m-Y') : date('d-m-Y'));

                $yearFolder = GoogleDrive::add_folder([$rootId], $year, 'google', true);
                $folder = GoogleDrive::add_folder([$yearFolder], $folderName, 'google', false);
            }

            GoogleDrive::add_file($folder, $file->getClientOriginalName(), $file, true, 'google');

            // path_drive deve contenere l'ID della cartella (convenzione progetto),
            // non quello del file: il bottone "Apri Drive" apre /folders/{id}
            return $folder;
        } catch (\Exception $e) {
            Log::error("EHS Drive upload error per evento {$obj->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Conta gli eventi raggruppati per una relazione (per i grafici).
     */
    private function countByRelation($query, string $fkColumn, string $modelClass, string $labelColumn): array
    {
        $counts = $query->select($fkColumn, DB::raw('COUNT(*) as totale'))
            ->whereNotNull($fkColumn)
            ->groupBy($fkColumn)
            ->pluck('totale', $fkColumn);

        $labels = $modelClass::whereIn('id', $counts->keys())->pluck($labelColumn, 'id');

        $data = [];
        $categories = [];
        foreach ($labels as $id => $label) {
            $data[] = $counts->get($id, 0);
            $categories[] = $label;
        }

        return ['data' => $data, 'categories' => $categories];
    }

    /**
     * Normalizza l'ora lavorativa del vecchio gestionale (1-8, 10=Extra time)
     * nel formato nuovo ('1°'..'8°', 'Extra time').
     */
    private function normalizeOraLavorativa($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = (string) $value;
        if (ctype_digit($v)) {
            $n = (int) $v;

            return $n >= 1 && $n <= 8 ? $n.'°' : 'Extra time';
        }

        return $v;
    }

    /**
     * Sincronizza i vecchi departments in hr_departments e ritorna la mappa old_id => uuid.
     * I reparti importati sono marcati disattivo per non comparire nelle tendine attive.
     */
    private function importDepartments(array &$stats): array
    {
        $map = [];
        $rows = DB::connection('mysql_old')->table('departments')->get();

        foreach ($rows as $row) {
            $existing = DB::table('hr_departments')
                ->whereRaw('LOWER(TRIM(reparto)) = ?', [mb_strtolower(trim($row->department))])
                ->first();

            if ($existing) {
                $map[$row->id] = $existing->id;
            } else {
                $id = (string) Str::uuid();
                DB::table('hr_departments')->insert([
                    'id' => $id,
                    'reparto' => $row->department,
                    'disattivo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $map[$row->id] = $id;
                $stats['departments']++;
            }
        }

        return $map;
    }

    /**
     * Importa una tabella di supporto dal vecchio DB.
     */
    private function importLookup(string $oldTable, string $modelClass, string $nameColumn): int
    {
        $count = 0;
        $rows = DB::connection('mysql_old')->table($oldTable)->get();

        foreach ($rows as $row) {
            $exists = $modelClass::where('old_id', $row->id)->exists();
            if (!$exists) {
                $modelClass::create([
                    $nameColumn => $row->$nameColumn,
                    'old_id' => $row->id,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Label per la qualifica.
     */
    private function qualificaLabel(?int $qualifica): string
    {
        return match ($qualifica) {
            1 => 'Operaio',
            2 => 'Impiegato',
            3 => 'Altro',
            default => '-',
        };
    }

    /**
     * Label per il tipo rilevazione.
     */
    private function tipoRilevazioneLabel(?int $tipo): string
    {
        return match ($tipo) {
            1 => 'Infortunio',
            2 => 'Near Miss (Inf.)',
            3 => 'Danno Ambientale',
            4 => 'Near Miss (Amb.)',
            5 => 'First AID',
            default => '-',
        };
    }
}
