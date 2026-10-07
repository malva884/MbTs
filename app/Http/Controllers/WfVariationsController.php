<?php

namespace App\Http\Controllers;

use App\Jobs\WfLogVariation;
use App\Models\User;
use App\Models\Utility;
use App\Models\WfCategory;
use App\Models\WfDocument;
use App\Models\WfRole;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use App\Models\WfVariations;
use App\Services\GoogleDrive;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class WfVariationsController extends Controller
{
    /**
     * Lista paginata delle variazioni (OL).
     *
     * Filtri: ol, stato (In-Approval/Approved/End), view (firma), visualiz (lettura).
     * view: 1 = da firmare, 2 = firmate, 3 = tutte
     * visualiz: 1 = visualizzate, 0 = non visualizzate
     */
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $viewBy = $request->get('view');
        $olBy = $request->get('ol');
        $statoBy = $request->get('stato');
        $visualizBy = $request->get('visualiz');

        $is_approver = WfUser::select('wf_users.id', 'wf_users.approval_start_date')
            ->join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->whereIn('wf_roles.role', WfVariations::$roleIdApproved)
            ->first();

        // Il flag 'Visualizzata' è riservato a chi ha il ruolo 'Visualizzatore'
        $is_viewer = WfUser::join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->where('wf_roles.role', 'Visualizzatore')
            ->exists();

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = WfVariations::select('wf_variations.*', 'wf_user_approvals.approval_action')
            ->leftJoin('wf_user_approvals', function ($join) {
                $join->on('wf_variations.id', '=', 'wf_user_approvals.model_id')
                    ->where('wf_user_approvals.model', '=', WfVariations::$modelName)
                    ->where('wf_user_approvals.user_id', '=', Auth::id())
                    ->where('wf_user_approvals.approval_action', '!=', 'Viewed');
            })
            ->Where(function ($query) use ($viewBy, $is_approver) {
                if ($viewBy == 1) {
                    $query->where('wf_variations.stato', 'In-Approval')
                        ->WhereNull('wf_user_approvals.model_id');

                    if (!empty($is_approver->id))
                        $query->whereDate('wf_variations.created_at', '>=', $is_approver->approval_start_date);
                }
                elseif ($viewBy == 2) {
                    $query->whereNotNull('wf_user_approvals.model_id');
                }
                elseif (!$viewBy) {
                    $query->where('wf_variations.stato', '!=', 'End');
                }
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->where('wf_variations.stato', $statoBy);
            })
            ->Where(function ($query) use ($olBy) {
                if ($olBy)
                    $query->where('wf_variations.ol', 'LIKE', '%' . $olBy . '%');
            })
            ->Where(function ($query) use ($visualizBy) {
                if ($visualizBy !== null && $visualizBy !== '') {
                    $method = $visualizBy ? 'whereIn' : 'whereNotIn';
                    $query->$method('wf_variations.id', function ($sub) {
                        $sub->select('model_id')
                            ->from('wf_user_approvals')
                            ->where('model', WfVariations::$modelName)
                            ->where('user_id', Auth::id())
                            ->where('approval_action', 'Viewed');
                    });
                }
            })
            ->orderBy($sortByName, $orderBy)
            ->distinct()
            ->paginate($request->itemsPerPage);

        // Stato visualizzazione per l'utente corrente (righe 'Viewed' in wf_user_approvals)
        $viewedMap = DB::table('wf_user_approvals')
            ->where('model', WfVariations::$modelName)
            ->where('user_id', Auth::id())
            ->where('approval_action', 'Viewed')
            ->whereIn('model_id', collect($objs->items())->pluck('id'))
            ->pluck('model_id')
            ->flip();

        $objs->getCollection()->transform(function ($obj) use ($viewedMap) {
            $obj->viewed = (bool) ($viewedMap[$obj->id] ?? false);
            return $obj;
        });

        return response()->json(['objs' => $objs, 'is_approver' => !empty($is_approver->id), 'is_viewer' => $is_viewer]);
    }

    /**
     * Variazioni in attesa di firma per l'utente corrente (badge/report).
     */
    public function pendingReport()
    {
        $isApprover = WfUser::select('wf_users.id', 'wf_users.approval_start_date')
            ->join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->whereIn('wf_roles.role', WfVariations::$roleIdApproved)
            ->first();

        if (empty($isApprover->id)) {
            return response()->json([
                'count' => 0,
                'items' => [],
                'is_approver' => false,
            ]);
        }

        $query = WfVariations::select('wf_variations.id', 'wf_variations.ol', 'wf_variations.revisione', 'wf_variations.created_at', 'wf_variations.stato')
            ->where('wf_variations.stato', 'In-Approval')
            ->where('wf_variations.visibile', true)
            ->whereDate('wf_variations.created_at', '>=', $isApprover->approval_start_date)
            ->leftJoin('wf_user_approvals', function ($join) {
                $join->on('wf_variations.id', '=', 'wf_user_approvals.model_id')
                    ->where('wf_user_approvals.model', '=', WfVariations::$modelName)
                    ->where('wf_user_approvals.user_id', '=', Auth::id())
                    ->where('wf_user_approvals.approval_action', '!=', 'Viewed');
            })
            ->whereNull('wf_user_approvals.model_id');

        $count = $query->count('wf_variations.id');

        $items = (clone $query)
            ->orderBy('wf_variations.created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'count' => $count,
            'items' => $items,
            'is_approver' => true,
        ]);
    }

    /**
     * Dettaglio variazione + approvatori + visualizzatori.
     */
    public function view($id)
    {
        $obj = WfVariations::where('wf_variations.id', $id)
            ->leftJoin('wf_categories', 'wf_variations.categoria_id', '=', 'wf_categories.id')
            ->select('wf_variations.*', 'wf_categories.categoria')
            ->first();

        if (empty($obj->id))
            return response()->json(['success' => false, 'message' => 'Messaggi.Variazione-Non-Trovata', 'color' => 'error'], 404);

        $obj->creator_name = optional(User::find($obj->creator))->full_name;

        $obj->approvals = WfUserApproval::join('users', 'user_id', 'users.id')
            ->select('wf_user_approvals.*', 'users.full_name')
            ->where('model_id', $id)
            ->where('model', WfVariations::$modelName)
            ->where('wf_user_approvals.approval_action', '!=', 'Viewed')
            ->orderBy('wf_user_approvals.created_at')
            ->get();

        // Visualizzatori: utenti con ruolo 'Visualizzatore' in wf_users, con stato lettura
        $viewedMap = DB::table('wf_user_approvals')
            ->where('model', WfVariations::$modelName)
            ->where('model_id', $id)
            ->where('approval_action', 'Viewed')
            ->pluck('created_at', 'user_id');

        $obj->viewers = WfUser::join('users', 'wf_users.user_id', '=', 'users.id')
            ->join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->select('users.id as user_id', 'users.full_name')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.disabled', false)
            ->where('wf_roles.role', 'Visualizzatore')
            ->orderBy('users.full_name')
            ->get()
            ->map(function ($u) use ($viewedMap) {
                $u->viewed = $viewedMap->has($u->user_id);
                $u->data_view = $viewedMap->get($u->user_id);
                return $u;
            });

        // Checkbox 'Visualizzata' riservata a chi ha il ruolo 'Visualizzatore'
        $obj->is_viewer = WfUser::join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->where('wf_roles.role', 'Visualizzatore')
            ->exists();

        return response()->json(['success' => true, 'obj' => $obj]);
    }

    /**
     * Categorie disponibili per il model WfVariations (select form creazione).
     */
    public function get_categorie()
    {
        $objs = WfCategory::where('model', WfVariations::$modelName)
            ->orderBy('categoria')
            ->get();

        return response()->json(['success' => true, 'objs' => $objs]);
    }

    /**
     * Verifica duplicati OL (warning nel form di creazione).
     */
    public function check(Request $request)
    {
        $objs = WfVariations::where('ol', 'LIKE', '%' . $request->ol . '%')
            ->where('stato', '!=', 'End')
            ->get(['id', 'ol', 'stato', 'revisione', 'created_at']);

        return response()->json($objs);
    }

    /**
     * Creazione variazione: PDF su Drive, visualizzatori, auto-approvazione creator, notifiche.
     */
    public function store(Request $request)
    {
        $request->validate([
            'ol' => 'required|integer',
            'revisione' => 'required|integer',
            'categoria' => 'required|string',
            'testo' => 'required|string',
        ]);

        $categoria = WfCategory::find($request->categoria);
        if (empty($categoria->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Categoria-Non-Trovata',
                'color' => 'error',
            ]);
        }

        $obj = new WfVariations();
        $obj->creator = Auth::id();
        $obj->ol = $request->ol;
        $obj->revisione = $request->revisione;
        $obj->testo = $request->testo;
        $obj->categoria_id = $categoria->id;
        $obj->stato = 'In-Approval';
        $obj->visibile = true;
        $obj->save();

        // Generazione PDF della comunicazione
        $nomeFile = $obj->ol . ' r' . $obj->revisione . '.pdf';
        $path = storage_path('app/pdf/');
        if (!is_dir($path))
            mkdir($path, 0777, true);

        $pdf = Pdf::loadView('pdf/wfVariazione', [
            'data' => [
                'ol' => $obj->ol,
                'rev' => $obj->revisione,
                'text' => $obj->testo,
            ],
        ]);
        $pdf->save($path . $nomeFile);

        // Cartella su Google Drive dentro la cartella della categoria
        $folderId = null;
        if (!empty($categoria->folder_drive)) {
            $folderId = GoogleDrive::add_folder([$categoria->folder_drive], $obj->ol, null, true);
            if ($folderId) {
                $obj->folder_drive = $folderId;
                $idFile = GoogleDrive::add_file($folderId, $nomeFile, $path . $nomeFile, true);
                if ($idFile) {
                    $obj->id_file_drive = $idFile;
                    WfDocument::addDocument(WfVariations::$modelName, $obj->id, $obj->ol, $nomeFile, 5, $idFile, $obj->id);
                }
                $obj->save();
            }
            else {
                Log::warning('WfVariations store: creazione cartella Drive fallita per OL ' . $obj->ol . ' - ' . GoogleDrive::$lastError);
            }
        }

        @unlink($path . $nomeFile);

        // I visualizzatori sono gli utenti con ruolo 'Visualizzatore' in wf_users:
        // nessuna riga precaricata — la lettura è registrata in wf_user_approvals ('Viewed')

        // Auto-approvazione se il creatore è un approvatore attivo
        $approver = WfUser::join('wf_roles', 'wf_users.role_id', '=', 'wf_roles.id')
            ->where('wf_users.model', WfVariations::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->whereIn('wf_roles.role', WfVariations::$roleIdApproved)
            ->select('wf_users.role_id', 'wf_users.approval_start_date')
            ->first();

        $completed = null;
        if (!empty($approver->role_id) && (empty($approver->approval_start_date) || $approver->approval_start_date <= date('Y-m-d')))
            $completed = WfUserApproval::approval($obj->id, WfVariations::$modelName, Auth::id(), $approver->role_id, 'Approved', null);

        // Auto-approvazione del creatore = tutti gli approvatori hanno firmato → End
        if ($completed) {
            $obj->stato = 'End';
            $obj->data_approvazione = date('Y-m-d');
            $obj->end_date = now();
            $obj->save();
            dispatch(new WfLogVariation($obj->id));
        }

        // Notifica email agli utenti abilitati
        try {
            $emails = Utility::users_notify(['wf_variazione_creata']);
            if (!empty($emails)) {
                $data = ['ol' => $obj->ol, 'revisione' => $obj->revisione];
                Mail::send('emails/email_variazione', compact('data'), function ($message) use ($emails, $obj) {
                    $message->to($emails)->subject('Nuova Variazione OL: ' . $obj->ol);
                });
            }
        }
        catch (\Exception $e) {
            Log::warning('WfVariations store: invio email fallito - ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Variazione-Creata',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Firma della variazione da parte dell'approvatore corrente.
     * Restituisce la prossima variazione da firmare (o '0').
     */
    public function approval(Request $request)
    {
        $obj = WfVariations::find($request->id);
        if (empty($obj->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Variazione-Non-Trovata',
                'color' => 'error',
            ]);
        }

        $is_approver = WfUser::select('id', 'approval_start_date')
            ->where('model', WfVariations::$modelName)
            ->where('user_id', Auth::id())
            ->where('disabled', false)
            ->first();

        if (empty($is_approver->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Errore',
                'color' => 'error',
            ]);
        }

        $completed = WfUserApproval::approval($request->id, WfVariations::$modelName, Auth::id(), $request->role_id, 'Approved', null);

        // Tutti gli approvatori hanno firmato: la variazione si conclude (End)
        if ($completed) {
            $obj->stato = 'End';
            $obj->data_approvazione = date('Y-m-d');
            $obj->end_date = now();
            $obj->save();
        }

        $next = WfVariations::select('wf_variations.*', 'wf_user_approvals.approval_action')
            ->leftJoin('wf_user_approvals', function ($join) {
                $join->on('wf_variations.id', '=', 'wf_user_approvals.model_id')
                    ->where('wf_user_approvals.model', '=', WfVariations::$modelName)
                    ->where('wf_user_approvals.user_id', '=', Auth::id())
                    ->where('wf_user_approvals.approval_action', '!=', 'Viewed');
            })
            ->where('wf_variations.stato', 'In-Approval')
            ->whereDate('wf_variations.created_at', '>=', $is_approver->approval_start_date)
            ->whereNull('wf_user_approvals.model_id')
            ->where('wf_variations.visibile', true)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!is_null($completed))
            dispatch(new WfLogVariation($obj->id));

        if (empty($next->id))
            $next = '0';

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Variazione-Approvata',
            'color' => 'success',
            'obj' => $next,
        ]);
    }

    /**
     * Documenti (PDF + log) associati alla variazione.
     */
    public function getDocument($id)
    {
        if ($id === 'undefined' || empty($id) || !preg_match('/^[a-f\d]{8}-(?:[a-f\d]{4}-){3}[a-f\d]{12}$/i', $id)) {
            Log::warning("Id Variazione non valido ricevuto in getDocument: '{$id}'");
            return response()->json([]);
        }

        $objs = DB::table('wf_documents')
            ->where('model_id', $id)
            ->orWhere('model_head_id', $id)
            ->distinct()
            ->orderBy('tipologia')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($objs);
    }

    /**
     * Segna la variazione come visualizzata/non visualizzata dall'utente corrente.
     */
    public function viewed(Request $request)
    {
        // Solo gli utenti con ruolo 'Visualizzatore' possono registrare la visualizzazione
        $role = WfRole::firstOrCreate(
            ['model' => WfVariations::$modelName, 'role' => 'Visualizzatore'],
            ['disabled' => false]
        );

        $is_viewer = WfUser::where('model', WfVariations::$modelName)
            ->where('user_id', Auth::id())
            ->where('role_id', $role->id)
            ->where('disabled', false)
            ->exists();

        if (!$is_viewer)
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Non-Autorizzato',
                'color' => 'error',
            ], 403);

        WfUserApproval::viewed(
            WfVariations::$modelName,
            $request->id,
            Auth::id(),
            $role->id,
            filter_var($request->viewed, FILTER_VALIDATE_BOOLEAN)
        );

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Salvato',
            'color' => 'success',
        ]);
    }

    /**
     * Aggiorna il percorso cartella Drive (unico campo modificabile, come nel vecchio edit).
     */
    public function update(Request $request, $id)
    {
        $obj = WfVariations::find($id);
        if (empty($obj->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Variazione-Non-Trovata',
                'color' => 'error',
            ]);
        }

        if ($request->has('folder_drive'))
            $obj->folder_drive = $request->folder_drive;
        if ($request->has('testo'))
            $obj->testo = $request->testo;
        if ($request->has('visibile'))
            $obj->visibile = filter_var($request->visibile, FILTER_VALIDATE_BOOLEAN);

        $obj->save();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Salvato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    /**
     * Chiusura variazione: stato End, end_date e generazione PDF log su Drive.
     */
    public function end($id)
    {
        $obj = WfVariations::find($id);
        if (empty($obj->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Variazione-Non-Trovata',
                'color' => 'error',
            ]);
        }

        $obj->stato = 'End';
        $obj->end_date = now();
        $obj->save();

        dispatch_sync(new WfLogVariation($obj->id));

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Variazione-Chiusa',
            'color' => 'success',
            'id_log_drive' => $obj->fresh()->id_log_drive,
        ]);
    }

    /**
     * Rigenera il PDF log della variazione (senza cambiare stato).
     */
    public function log($id)
    {
        $obj = WfVariations::find($id);
        if (empty($obj->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Variazione-Non-Trovata',
                'color' => 'error',
            ]);
        }

        dispatch_sync(new WfLogVariation($obj->id));

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Salvato',
            'color' => 'success',
            'id_log_drive' => $obj->fresh()->id_log_drive,
        ]);
    }

    /**
     * Eliminazione variazione (con documenti, visualizzatori e cartella Drive).
     */
    public function destroy($id)
    {
        $obj = WfVariations::find($id);
        if (empty($obj->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Messaggi.Variazione-Non-Trovata',
                'color' => 'error',
            ]);
        }

        try {
            if (!empty($obj->folder_drive))
                GoogleDrive::delated($obj->folder_drive, 'google');
        }
        catch (\Exception $e) {
            Log::warning('WfVariations destroy: eliminazione cartella Drive fallita - ' . $e->getMessage());
        }

        DB::table('wf_user_approvals')->where('model', WfVariations::$modelName)->where('model_id', $id)->delete();
        DB::table('wf_documents')->where('model_id', $id)->where('model', WfVariations::$modelName)->delete();
        $obj->delete();

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Variazione-Eliminata',
            'color' => 'success',
        ]);
    }

    public function userOpenFile(Request $request, $id)
    {
        $document = WfDocument::find($id);
        $document->userOpenFile = Auth::user()->matricola;
        $document->save();
    }

    public function getFile(Request $request)
    {
        $document = WfDocument::where('userOpenFile', $request->user)->first();

        $idFile = null;
        if (!empty($document->id)) {
            $idFile = $document->id_file_drive;
            $document->userOpenFile = null;
            $document->save();
        }

        return response()->json([
            'success' => true,
            'idFile' => $idFile,
        ]);
    }
}
