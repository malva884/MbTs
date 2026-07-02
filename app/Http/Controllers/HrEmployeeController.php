<?php

namespace App\Http\Controllers;

use App\Jobs\EmployeeDriver;
use App\Jobs\EmployeeSyncPortal;
use App\Jobs\HrCreazioneFormazioniAutomatiche;
use App\Jobs\RevokeEmployeeAccesses;
use App\Models\HrCostCenter;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrEmployeeAccess;
use App\Models\HrEmployeeTrainingMandatory;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrTraining;
use App\Models\EmployeeShift;
use App\Models\DipEmployee;
use App\Models\DipUser;
use App\Services\GoogleDrive;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HrEmployeeController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $dipendenteBy = $request->get('dipendente');
        $matricolaBy = $request->get('matricola');
        $dimessoBy = $request->get('dimesso');
        $repartoBy = $request->get('reparto');

        if (empty($sortByName)) {
            $sortByName = 'nome_completo';
            $orderBy = 'asc';
        }
        $objs = DB::table('hr_employees')
            ->select('hr_employees.*','hr_departments.reparto')
            ->leftJoin('hr_departments','hr_employees.reparto_id','hr_departments.id')
            ->Where(function ($query) use ($dipendenteBy) {
                if ($dipendenteBy)
                    $query->Where('nome_completo', 'LIKE', '%' . $dipendenteBy . '%');
            })
            ->Where(function ($query) use ($matricolaBy) {
                if ($matricolaBy)
                    $query->Where('matricola', 'LIKE', '%' . $matricolaBy . '%');
            })
            ->Where(function ($query) use ($repartoBy) {
                if ($repartoBy)
                    $query->Where('reparto_id', $repartoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function import()
    {
        try {
            $ems = DB::connection('mysql_old')->table('employees')
                ->select('employees.*', 'department_jobs.department')
                ->join('department_jobs', 'employees.department', 'department_jobs.id')
                ->get();

            $importedCount = 0;

            foreach ($ems as $em) {
                $o = HrEmployee::where('matricola', $em->matricola)->first();
                if (empty($o->id)) {
                    $o = new HrEmployee();
                }

                $r = HrDepartment::where('reparto', $em->department)->first();
                if (empty($r->id)) {
                    $r = new HrDepartment();
                    $r->Reparto = $em->department;
                    $r->save();
                }

                $c = HrCostCenter::where('valore', $em->centro)->first();
                if (empty($c->id)) {
                    $c = new HrCostCenter();
                    $c->centro_di_costo = ucwords(str_replace("_", " ", $em->centro));
                    $c->valore = $em->centro;
                    $c->disattivo = false;
                    $c->save();
                }

                $o->nome = $em->nome;
                $o->cognome = $em->cognome;
                $o->nome_completo = $em->nome . ' ' . $em->cognome;
                $o->matricola = $em->matricola;
                $o->sesso = '';
                $o->email = $em->email;
                $o->data_assunzione = $em->data_assunzione;
                $o->data_nascita = $em->data_nasciata;
                $o->tel = $em->tel;
                $o->tel_az = $em->tel_az;
                $o->valutatore = !empty($em->valutatore);
                $o->dimesso = !empty($em->resigned);
                $o->path_drive = $em->path_drive;
                $o->reparto_id = $r->id;
                $o->ruolo_id = '9E19D63E-EE5B-412A-83DB-35D0A95534BA';
                $o->centro_id = $c->id;
                $o->numero_anni_visita_medica = 4;
                $o->company_id = 'metallurgica';
                $o->save();

                $importedCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Importazione completata con successo. Importati/Aggiornati $importedCount dipendenti.",
                'color' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Errore durante importazione dipendenti: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'importazione: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // 1. Validazione dei dati in ingresso
        $request->validate([
            'nome' => 'required|string|max:100',
            'cognome' => 'required|string|max:100',
            'matricola' => 'required|string|max:50|unique:hr_employees,matricola',
            'company_id' => 'required|string',
            'reparto_id' => 'required|uuid',
            'ruolo_ids' => 'required|array',
            'ruolo_ids.*' => 'uuid|exists:hr_roles,id',
            'centro_id' => 'required|uuid',
            'sesso' => 'required|string|in:m,f',
            'email' => 'nullable|email|max:150',
            'data_assunzione' => 'nullable|date',
            'data_nascita' => 'nullable|date',
            'data_ultima_visita' => 'nullable|date',
            'numero_anni_visita_medica' => 'nullable|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            $obj = new HrEmployee();
            
            // Formattazione coerente con i nomi composti (es. "Jean Paul")
            $obj->nome = Str::title(trim($request->nome));
            $obj->cognome = Str::title(trim($request->cognome));
            $obj->nome_completo = $obj->cognome . ' ' . $obj->nome;
            
            $obj->email = $request->email;
            $obj->matricola = trim($request->matricola);
            $obj->data_assunzione = $request->data_assunzione;
            $obj->data_nascita = $request->data_nascita;
            $obj->tel = $request->tel;
            $obj->tel_az = $request->tel_az;
            $obj->valutatore = !empty($request->valutatore);
            $obj->reparto_id = $request->reparto_id;
            $obj->centro_id = $request->centro_id;
            $obj->sesso = $request->sesso;
            $obj->data_ultima_visita = $request->data_ultima_visita;
            $obj->numero_anni_visita_medica = $request->numero_anni_visita_medica ?? 4;

            // Calcolo pulito della scadenza tramite Carbon
            if ($obj->data_ultima_visita && $obj->numero_anni_visita_medica) {
                $obj->data_scadenza_visita = Carbon::parse($obj->data_ultima_visita)
                    ->addYears($obj->numero_anni_visita_medica)
                    ->toDateString();
            }

            // Assegnazione casuale dell'avatar
            if ($obj->sesso == 'm') {
                $obj->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
            } else {
                $obj->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
            }

            $obj->company_id = $request->company_id;

            // Gestione protetta di Google Drive per evitare blocco se l'API di Google fallisce
            try {
                $pathDrive = GoogleDrive::add_folder(
                    ['1LQ8Pw4zkaRqfHbEdb98IJOQ_ndj6x9hl'], 
                    $obj->matricola . ' ( ' . $obj->nome_completo . ' )', 
                    'google', 
                    false
                );
                $obj->path_drive = $pathDrive ?: null;
            } catch (\Exception $driveEx) {
                Log::error("Errore creazione cartella Google Drive per il dipendente {$obj->nome_completo}: " . $driveEx->getMessage());
                $obj->path_drive = null;
            }

            if (empty($obj->path_drive)) {
                Log::error("Creazione cartella Google Drive fallita per il dipendente {$obj->nome_completo} (matricola {$obj->matricola}). Il job delle formazioni non sara dispatchato.");
            }

            $obj->save();

            $obj->roles()->sync($request->ruolo_ids);

            // Verifica se il dipendente esiste già nel DB Dipendenti
            $existingDipEmployee = DipEmployee::where('employee_id', $obj->matricola)->first();
            $employeeExistsInDipendenti = $existingDipEmployee !== null;

            // Sincronizzazione con il progetto Dipendenti: crea Employee + User nel DB Dipendenti
            $this->syncToDipendentiProject($obj);

            DB::commit();

            // Creazione formazioni obbligatorie in coda solo se path_drive e valido
            if (!empty($obj->path_drive)) {
                dispatch(new HrCreazioneFormazioniAutomatiche($obj->id, Auth::id()));
            }

            // Sincronizzazione vecchio portale in coda
            dispatch(new EmployeeSyncPortal($obj->id));

            $response = [
                'success' => true,
                'message' => 'Messaggi.Dipendente-Aggiunto',
                'color' => 'success',
                'obj' => $obj,
            ];

            // Aggiungi avviso se il dipendente esisteva già nel DB Dipendenti
            if ($employeeExistsInDipendenti) {
                $response['warning'] = 'Il dipendente era già presente nel database Dipendenti ed è stato aggiornato.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore durante il salvataggio del dipendente: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante il salvataggio: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $obj = HrEmployee::find($id);
        if (!$obj) {
            return response()->json([
                'success' => false,
                'message' => 'Dipendente non trovato',
                'color' => 'error'
            ], 404);
        }

        // 1. Validazione dei dati in ingresso
        $request->validate([
            'nome' => 'required|string|max:100',
            'cognome' => 'required|string|max:100',
            'matricola' => 'required|string|max:50|unique:hr_employees,matricola,' . $id . ',id',
            'company_id' => 'required|string',
            'reparto_id' => 'required|uuid',
            'ruolo_ids' => 'required|array',
            'ruolo_ids.*' => 'uuid|exists:hr_roles,id',
            'centro_id' => 'required|uuid',
            'sesso' => 'required|string|in:m,f',
            'email' => 'nullable|email|max:150',
            'data_assunzione' => 'nullable|date',
            'data_nascita' => 'nullable|date',
            'data_ultima_visita' => 'nullable|date',
            'numero_anni_visita_medica' => 'nullable|integer|min:0'
        ]);

        try {
            DB::beginTransaction();

            // Rileva se nome, cognome o matricola sono cambiati per aggiornare Google Drive
            $formattedNome = Str::title(trim($request->nome));
            $formattedCognome = Str::title(trim($request->cognome));
            $formattedMatricola = trim($request->matricola);

            $editDrive = false;
            if ($obj->nome !== $formattedNome || $obj->cognome !== $formattedCognome || $obj->matricola !== $formattedMatricola) {
                $editDrive = true;
            }

            $obj->nome = $formattedNome;
            $obj->cognome = $formattedCognome;
            $obj->nome_completo = $obj->cognome . ' ' . $obj->nome;
            $obj->email = $request->email;
            $obj->matricola = $formattedMatricola;
            $obj->data_assunzione = $request->data_assunzione;
            $obj->data_nascita = $request->data_nascita;
            $obj->tel = $request->tel;
            $obj->tel_az = $request->tel_az;
            $obj->reparto_id = $request->reparto_id;
            $obj->centro_id = $request->centro_id;
            
            // Gestione coerente dell'avatar: assegna solo se sesso è cambiato o se manca l'avatar
            if ($obj->sesso !== $request->sesso || empty($obj->avatar)) {
                if ($request->sesso == 'm') {
                    $obj->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
                } else {
                    $obj->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
                }
            }
            $obj->sesso = $request->sesso;

            $obj->dimesso = !empty($request->dimesso);
            $obj->valutatore = !empty($request->valutatore);
            $obj->company_id = $request->company_id;

            // Salvataggio dei dati visita medica (corregge bug in cui non venivano salvati nell'update)
            $obj->data_ultima_visita = $request->data_ultima_visita;
            $obj->numero_anni_visita_medica = $request->numero_anni_visita_medica ?? 4;

            // Ricalcolo Carbon della data scadenza visita medica
            if ($obj->data_ultima_visita && $obj->numero_anni_visita_medica) {
                $obj->data_scadenza_visita = Carbon::parse($obj->data_ultima_visita)
                    ->addYears($obj->numero_anni_visita_medica)
                    ->toDateString();
            } else {
                $obj->data_scadenza_visita = null;
            }

            $obj->save();

            $obj->roles()->sync($request->ruolo_ids);

            // Verifica se il dipendente esiste già nel DB Dipendenti
            $existingDipEmployee = DipEmployee::where('employee_id', $obj->matricola)->first();
            $employeeExistsInDipendenti = $existingDipEmployee !== null;

            // Sincronizzazione con il progetto Dipendenti: aggiorna Employee + User nel DB Dipendenti
            $this->syncToDipendentiProject($obj);

            DB::commit();

            // Rinominazione asincrona e sicura della cartella Google Drive tramite Job in coda
            if ($editDrive && !empty($obj->path_drive)) {
                dispatch(new EmployeeDriver($obj->id));
            }

            $response = [
                'success' => true,
                'message' => 'Messaggi.Dipendente-Modificato',
                'color' => 'success',
                'obj' => $obj
            ];

            // Aggiungi avviso se il dipendente esisteva già nel DB Dipendenti
            if ($employeeExistsInDipendenti) {
                $response['warning'] = 'Il dipendente era già presente nel database Dipendenti ed è stato aggiornato.';
            }

            return response()->json($response);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore durante la modifica del dipendente: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la modifica: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function view($id)
    {
        $obj = HrEmployee::with(['department', 'centerCost', 'roles'])->find($id);

        return response()->json($obj);
    }

    public function syncToDipendenti($id)
    {
        $employee = HrEmployee::find($id);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Dipendente non trovato',
                'color' => 'error'
            ], 404);
        }

        try {
            $this->syncToDipendentiProject($employee);

            return response()->json([
                'success' => true,
                'message' => 'Sincronizzazione completata con successo',
                'color' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error("Errore sincronizzazione manuale per dipendente {$employee->matricola}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante la sincronizzazione: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function addQuickAbsence(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'data' => 'required|date',
            'tipologia' => 'required|integer|in:3,4', // 3=Malattia, 4=Assenza
            'note' => 'nullable|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            $employee = HrEmployee::find($request->employee_id);

            // Verifica se esiste già un'assenza per questo dipendente e giorno
            $existing = HrHoursRequestedDetail::where('dipendente_matricola', $employee->matricola)
                ->where('data', $request->data)
                ->whereIn('tipologia', [3, 4])
                ->where('confermato', true)
                ->whereHas('richiesta', function($query) {
                    $query->where('stato', 1);
                })
                ->first();

            if ($existing) {
                // Aggiorna la tipologia esistente invece di creare un duplicato
                $existing->tipologia = $request->tipologia;
                $existing->save();
                $richiesta = $existing->richiesta;
                $richiesta->tipologia = $request->tipologia;
                $richiesta->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Assenza aggiornata con successo',
                    'color' => 'success'
                ]);
            }

            // Crea la richiesta principale
            $richiesta = new HrHoursRequested();
            $richiesta->dipendente_matricola = $employee->matricola;
            $richiesta->dipendente_nome = $employee->nome;
            $richiesta->dipendente_cognome = $employee->cognome;
            $richiesta->tipologia = $request->tipologia;
            $richiesta->data_richiesta = Carbon::now();
            $richiesta->centro_di_costo = $employee->centerCost?->centro_di_costo;
            $richiesta->note = $request->note;
            $richiesta->stato = 1; // Auto-approvato
            $richiesta->bacheca_id = 0; // 0 = quick absence (auto-approved, no external bacheca)
            $richiesta->bacheca_dipendente_id = $employee->matricola;
            $richiesta->save();

            // Crea il dettaglio del giorno
            $dettaglio = new HrHoursRequestedDetail();
            $dettaglio->richiesta_id = $richiesta->id;
            $dettaglio->dipendente_matricola = $employee->matricola;
            $dettaglio->data = $request->data;
            $dettaglio->confermato = true;
            $dettaglio->tipologia = $request->tipologia;
            $dettaglio->bacheca_id = $richiesta->bacheca_id;
            $dettaglio->bacheca_dipendente_id = $richiesta->bacheca_dipendente_id;
            $dettaglio->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assenza inserita con successo',
                'color' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore inserimento assenza rapida: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'inserimento: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function updateQuickAbsence(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'data' => 'required|date',
            'tipologia' => 'required|integer|in:3,4', // 3=Malattia, 4=Assenza
        ]);

        try {
            DB::beginTransaction();

            $employee = HrEmployee::find($request->employee_id);

            // Find all existing details for this employee and date with different tipologia
            $dettagli = HrHoursRequestedDetail::where('dipendente_matricola', $employee->matricola)
                ->where('data', $request->data)
                ->where('tipologia', '!=', $request->tipologia)
                ->where('confermato', true)
                ->whereHas('richiesta', function($query) {
                    $query->where('stato', 1);
                })
                ->get();

            if ($dettagli->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assenza non trovata o non modificabile',
                    'color' => 'error'
                ], 404);
            }

            // Update all matching details and their parent requests
            $richiestaIds = [];
            foreach ($dettagli as $dettaglio) {
                $dettaglio->tipologia = $request->tipologia;
                $dettaglio->save();
                $richiestaIds[] = $dettaglio->richiesta_id;
            }

            // Update parent requests
            HrHoursRequested::whereIn('id', array_unique($richiestaIds))->update(['tipologia' => $request->tipologia]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assenza aggiornata con successo',
                'color' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore aggiornamento assenza rapida: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function deleteQuickAbsence(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|uuid|exists:hr_employees,id',
            'data' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $employee = HrEmployee::find($request->employee_id);

            // Find ALL existing details for this employee and date (malattia or assenza)
            $dettagli = HrHoursRequestedDetail::where('dipendente_matricola', $employee->matricola)
                ->where('data', $request->data)
                ->whereIn('tipologia', [3, 4])
                ->where('confermato', true)
                ->whereHas('richiesta', function($query) {
                    $query->where('stato', 1);
                })
                ->get();

            if ($dettagli->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assenza non trovata o non eliminabile',
                    'color' => 'error'
                ], 404);
            }

            $richiestaIds = $dettagli->pluck('richiesta_id')->unique()->toArray();

            // Delete ALL matching details
            HrHoursRequestedDetail::where('dipendente_matricola', $employee->matricola)
                ->where('data', $request->data)
                ->whereIn('tipologia', [3, 4])
                ->where('confermato', true)
                ->whereHas('richiesta', function($query) {
                    $query->where('stato', 1);
                })
                ->delete();

            // Delete parent requests that have no more details
            foreach ($richiestaIds as $richiestaId) {
                $otherDetails = HrHoursRequestedDetail::where('richiesta_id', $richiestaId)->count();
                if ($otherDetails === 0) {
                    HrHoursRequested::where('id', $richiestaId)->delete();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Assenza eliminata con successo',
                'color' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Errore eliminazione assenza rapida: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione: ' . $e->getMessage(),
                'color' => 'error'
            ], 500);
        }
    }

    public function get_dipendenti()
    {
        $objs = DB::table('hr_employees')
            ->select('id','nome_completo','matricola',DB::raw("CONCAT(nome_completo,' - ',matricola) AS titolo"))
            ->where('dimesso',false)
            ->orderBy('nome_completo', 'asc') //order in descending order
            ->get();

        return response()->json($objs);
    }

    /**
     * Sincronizza il dipendente MbTs con il progetto Dipendenti:
     * crea o aggiorna Employee + User nel database del progetto Dipendenti.
     */
    private function syncToDipendentiProject(HrEmployee $employee): void
    {
        try {
            // Cerca un dipendente esistente nel DB Dipendenti tramite la matricola
            $dipEmployee = DipEmployee::where('employee_id', $employee->matricola)->first();

            if (!$dipEmployee) {
                // --- CREAZIONE ---
                $dipEmployee = new DipEmployee();
                $dipEmployee->name = $employee->nome;
                $dipEmployee->cognome = $employee->cognome;
                $dipEmployee->employee_id = $employee->matricola;
                $dipEmployee->email = $employee->email;
                $dipEmployee->phone = $employee->tel;
                $dipEmployee->date_of_birth = $employee->data_nascita;
                $dipEmployee->hire_date = $employee->data_assunzione;
                $dipEmployee->company_id = $this->mapCompanyId($employee->company_id);
                $dipEmployee->save();

                // Genera username univoco (nome.cognome)
                $baseUsername = strtolower(preg_replace('/\s+/', '', Str::ascii($employee->nome . '.' . $employee->cognome)));
                $username = $baseUsername;
                $counter = 1;
                while (DipUser::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Crea l'utente nel DB Dipendenti
                $dipUser = new DipUser();
                $dipUser->nome = $employee->nome;
                $dipUser->cognome = $employee->cognome;
                $dipUser->email = $employee->email;
                $dipUser->username = $username;
                $dipUser->password = Hash::make('22' . $employee->matricola);
                $dipUser->role = [DipUser::ROLE_EMPLOYEE];
                $dipUser->password_reset_required = true;
                $dipUser->save();

                // Collegamento bidirezionale
                $dipEmployee->user_id = $dipUser->id;
                $dipEmployee->save();
                $dipUser->employee_id = $dipEmployee->id;
                $dipUser->save();
            } else {
                // --- AGGIORNAMENTO ---
                $dipEmployee->name = $employee->nome;
                $dipEmployee->cognome = $employee->cognome;
                $dipEmployee->employee_id = $employee->matricola;
                $dipEmployee->email = $employee->email;
                $dipEmployee->phone = $employee->tel;
                $dipEmployee->date_of_birth = $employee->data_nascita;
                $dipEmployee->hire_date = $employee->data_assunzione;
                $dipEmployee->company_id = $this->mapCompanyId($employee->company_id);
                $dipEmployee->save();

                // Aggiorna l'utente associato se esiste
                if ($dipEmployee->user_id) {
                    $dipUser = DipUser::find($dipEmployee->user_id);
                    if ($dipUser) {
                        $dipUser->nome = $employee->nome;
                        $dipUser->cognome = $employee->cognome;
                        $dipUser->email = $employee->email;
                        $dipUser->save();
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Errore sincronizzazione progetto Dipendenti per matricola {$employee->matricola}: " . $e->getMessage());
        }
    }

    /**
     * Mappa il company_id di MbTs con l'ID della tabella companies del progetto Dipendenti.
     */
    private function mapCompanyId(string $mbtsCompanyId): ?int
    {
        $map = [
            'metallurgica' => 1,
            'optotec' => 2,
        ];

        return $map[$mbtsCompanyId] ?? null;
    }

    public function report(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $month = $request->get('month');
        $repartoId = $request->get('reparto_id');
        $centroDiCosto = $request->get('centro_di_costo');

        try {
            $startDate = Carbon::create($year, $month ? (int)$month : 1, 1)->startOfMonth();
            $endDate = $month ? $startDate->copy()->endOfMonth() : Carbon::create($year, 12, 31)->endOfMonth();
        } catch (\Exception $e) {
            return response()->json(['message' => 'Formato data non valido'], 400);
        }

        $query = HrEmployee::with(['department', 'centerCost'])
            ->where('dimesso', false);

        if ($repartoId) {
            $query->where('reparto_id', $repartoId);
        }

        if ($centroDiCosto) {
            $query->where('centro_id', $centroDiCosto);
        }

        $employees = $query->orderBy('cognome')->orderBy('nome')->get();
        $matricole = $employees->pluck('matricola')->filter()->toArray();

        $absencesQuery = HrHoursRequestedDetail::with('richiesta')
            ->whereBetween('data', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if (!empty($matricole)) {
            $absencesQuery->whereIn('dipendente_matricola', $matricole);
        }

        $absences = $absencesQuery->where('confermato', true)->get();

        // KPI totali
        $totalDays = $absences->count();
        $byTipologia = $absences->groupBy('richiesta.tipologia')->map(fn($group) => $group->count());
        $byMonth = $absences->groupBy(fn($item) => Carbon::parse($item->data)->format('Y-m'))->map(fn($group) => $group->count());
        $byEmployee = $absences->groupBy('dipendente_matricola')->map(fn($group) => $group->count())->sortDesc()->take(10);

        // Tabella dettagliata
        $details = $absences->map(function($item) use ($employees) {
            $employee = $employees->firstWhere('matricola', $item->dipendente_matricola);
            return [
                'dipendente' => $employee->nome_completo ?? 'N/A',
                'matricola' => $item->dipendente_matricola,
                'reparto' => $employee->department?->nome ?? 'N/A',
                'centro_di_costo' => $employee->centerCost?->nome ?? 'N/A',
                'data' => $item->data,
                'tipologia' => $item->richiesta->tipologia,
                'tipologia_testo' => $this->getTipologiaTesto($item->richiesta->tipologia),
                'ora_inizio' => $item->ora_inizio,
                'ora_fine' => $item->ora_fine,
                'stato' => $item->richiesta->stato,
            ];
        })->sortBy('data')->values();

        return response()->json([
            'kpi' => [
                'total_days' => $totalDays,
                'by_tipologia' => $byTipologia,
                'by_month' => $byMonth,
                'by_employee' => $byEmployee,
            ],
            'details' => $details,
        ]);
    }

    public function dimissioni($id)
    {
        $employee = HrEmployee::findOrFail($id);

        if ($employee->dimesso) {
            return response()->json(['message' => 'Il dipendente risulta già dimesso'], 400);
        }

        $employee->dimesso = true;
        $employee->save();

        $accessCount = HrEmployeeAccess::where('employee_id', $id)->count();

        RevokeEmployeeAccesses::dispatch($id);

        return response()->json([
            'message' => 'Dimissioni registrate con successo. La revoca degli accessi Google Drive è in corso in background.',
            'pending_accesses' => $accessCount,
        ]);
    }

    public function presenze(Request $request)
    {
        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $repartoId = $request->get('reparto_id');
        $centroDiCosto = $request->get('centro_di_costo');

        try {
            $date = Carbon::parse($month . '-01');
        } catch (\Exception $e) {
            return response()->json(['message' => 'Formato mese non valido. Usa YYYY-MM'], 400);
        }

        $startDate = $date->copy()->startOfMonth();
        $endDate = $date->copy()->endOfMonth();
        $daysInMonth = $date->daysInMonth;

        $query = HrEmployee::with(['department', 'centerCost'])
            ->where('dimesso', false);

        if ($repartoId) {
            $query->where('reparto_id', $repartoId);
        }

        if ($centroDiCosto) {
            $query->where('centro_id', $centroDiCosto);
        }

        $employees = $query->orderBy('cognome')->orderBy('nome')->get();

        $matricole = $employees->pluck('matricola')->filter()->toArray();

        $absencesQuery = HrHoursRequestedDetail::with('richiesta')
            ->whereBetween('data', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        if (!empty($matricole)) {
            $absencesQuery->whereIn('dipendente_matricola', $matricole);
        }

        $absences = $absencesQuery
            ->where('confermato', true)
            ->get()
            ->groupBy('dipendente_matricola');

        // Fetch shifts from mysql_dipendenti using employee_id instead of matricola
        $shifts = DB::connection('mysql_dipendenti')
            ->table('employee_shifts as es')
            ->join('employees as e', 'es.employee_id', '=', 'e.id')
            ->whereIn('e.employee_id', $matricole)
            ->whereBetween('es.shift_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->select('es.*', 'e.employee_id as matricola')
            ->get()
            ->groupBy('matricola');

        $italianHolidays = $this->getItalianHolidays($date->year);

        $matrix = [];
        foreach ($employees as $employee) {
            $row = [
                'id' => $employee->id,
                'matricola' => $employee->matricola,
                'nome_completo' => $employee->nome_completo,
                'reparto' => $employee->department?->nome ?? '',
                'centro_di_costo' => $employee->centerCost?->nome ?? '',
            ];

            $employeeAbsences = $absences->get($employee->matricola, collect());
            $employeeShifts = $shifts->get($employee->matricola, collect());

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $currentDate = $date->copy()->day($day);
                $dateStr = $currentDate->format('Y-m-d');

                $dayData = [
                    'data' => $dateStr,
                    'giorno_settimana' => $currentDate->dayName,
                    'festivo' => in_array($dateStr, $italianHolidays),
                    'assenza' => null,
                    'turno' => null,
                ];

                foreach ($employeeAbsences as $absence) {
                    if ($absence->data === $dateStr) {
                        $dayData['assenza'] = [
                            'tipologia' => $absence->richiesta->tipologia,
                            'tipologia_testo' => $this->getTipologiaTesto($absence->richiesta->tipologia),
                            'ora_inizio' => $absence->ora_inizio,
                            'ora_fine' => $absence->ora_fine,
                        ];
                        break;
                    }
                }

                foreach ($employeeShifts as $shift) {
                    if ((string) $shift->shift_date === $dateStr) {
                        $dayData['turno'] = [
                            'type' => $shift->type,
                            'machine' => $shift->machine,
                        ];
                        break;
                    }
                }

                $row["day_{$day}"] = $dayData;
            }

            $matrix[] = $row;
        }

        return response()->json([
            'month' => $month,
            'days_in_month' => $daysInMonth,
            'holidays' => $italianHolidays,
            'employees' => $matrix,
        ]);
    }

    private function getItalianHolidays($year)
    {
        $holidays = [];

        $fixedHolidays = [
            '01-01', // Capodanno
            '01-06', // Epifania
            '04-25', // Festa della Liberazione
            '05-01', // Festa del Lavoro
            '06-02', // Festa della Repubblica
            '08-15', // Ferragosto
            '11-01', // Tutti i Santi
            '12-08', // Immacolata
            '12-25', // Natale
            '12-26', // Santo Stefano
        ];

        foreach ($fixedHolidays as $date) {
            $holidays[] = $year . '-' . $date;
        }

        $easter = $this->getEasterDate($year);
        $holidays[] = $easter->format('Y-m-d'); // Pasqua
        $holidays[] = $easter->copy()->addDay()->format('Y-m-d'); // Pasquetta

        return $holidays;
    }

    private function getEasterDate($year)
    {
        $a = $year % 19;
        $b = floor($year / 100);
        $c = $year % 100;
        $d = floor($b / 4);
        $e = $b % 4;
        $f = floor((($b + 8) / 25));
        $g = floor((($b - $f + 1) / 3));
        $h = ((19 * $a) + $b - $d - $g + 15) % 30;
        $i = floor($c / 4);
        $k = $c % 4;
        $l = (32 + (2 * $e) + (2 * $i) - $h - $k) % 7;
        $m = floor((($a + (11 * $h) + (22 * $l)) / 451));

        $month = floor((($h + $l - (7 * $m) + 114) / 31));
        $day = ((($h + $l - (7 * $m) + 114) % 31) + 1);

        return Carbon::create($year, $month, $day);
    }

    private function getTipologiaTesto($tipologiaId)
    {
        switch ($tipologiaId) {
            case 1: return 'Ferie';
            case 2: return '104';
            case 3: return 'Malattia';
            case 4: return 'Assenza';
            case 5: return 'Permesso';
            default: return 'Sconosciuta';
        }
    }
}
