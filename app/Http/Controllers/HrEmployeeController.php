<?php

namespace App\Http\Controllers;

use App\Jobs\EmployeeDriver;
use App\Jobs\EmployeeSyncPortal;
use App\Jobs\HrCreazioneFormazioniObligatorie;
use App\Models\HrCostCenter;
use App\Models\HrDepartment;
use App\Models\HrEmployee;
use App\Models\HrEmployeeTrainingMandatory;
use App\Models\HrTraining;
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
                $obj->path_drive = GoogleDrive::add_folder(
                    ['1LQ8Pw4zkaRqfHbEdb98IJOQ_ndj6x9hl'], 
                    $obj->matricola . ' ( ' . $obj->nome_completo . ' )', 
                    'google', 
                    false
                );
            } catch (\Exception $driveEx) {
                Log::error("Errore creazione cartella Google Drive per il dipendente {$obj->nome_completo}: " . $driveEx->getMessage());
                $obj->path_drive = null;
            }

            $obj->save();

            $obj->roles()->sync($request->ruolo_ids);

            // Sincronizzazione con il progetto Dipendenti: crea Employee + User nel DB Dipendenti
            $this->syncToDipendentiProject($obj);

            DB::commit();

            // Creazione formazioni obbligatorie in coda
            dispatch(new HrCreazioneFormazioniObligatorie($obj->id, Auth::id()));

            // Sincronizzazione vecchio portale in coda
            dispatch(new EmployeeSyncPortal($obj->id));

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Dipendente-Aggiunto',
                'color' => 'success',
                'obj' => $obj,
            ]);

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

            // Sincronizzazione con il progetto Dipendenti: aggiorna Employee + User nel DB Dipendenti
            $this->syncToDipendentiProject($obj);

            DB::commit();

            // Rinominazione asincrona e sicura della cartella Google Drive tramite Job in coda
            if ($editDrive && !empty($obj->path_drive)) {
                dispatch(new EmployeeDriver($obj->id));
            }

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Dipendente-Modificato',
                'color' => 'success',
                'obj' => $obj
            ]);

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
            $dipEmployee = DipEmployee::where('matricola', $employee->matricola)->first();

            if (!$dipEmployee) {
                // --- CREAZIONE ---
                $dipEmployee = new DipEmployee();
                $dipEmployee->name = $employee->nome;
                $dipEmployee->cognome = $employee->cognome;
                $dipEmployee->matricola = $employee->matricola;
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
                $dipEmployee->matricola = $employee->matricola;
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
}
