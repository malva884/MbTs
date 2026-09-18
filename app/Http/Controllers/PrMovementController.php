<?php

namespace App\Http\Controllers;

use App\Models\PrMovement;
use App\Models\Utility;
use App\Models\WfUser;
use App\Models\WfUserApproval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PrMovementController extends Controller
{
    private function approver()
    {
        return WfUser::select('wf_users.id', 'wf_users.role_id', 'wf_users.approval_start_date')
            ->join('wf_roles', 'wf_users.role_id', 'wf_roles.id')
            ->where('wf_users.model', PrMovement::$modelName)
            ->where('wf_users.user_id', Auth::id())
            ->where('wf_users.disabled', false)
            ->whereIn('wf_roles.role', PrMovement::$roleIdApproved)
            ->first();
    }

    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $materialeBy = $request->get('materiale');
        $documentoBy = $request->get('documento');
        $tipoBy = $request->get('tipo_movimento');
        $statoBy = $request->get('stato');
        $dataDa = $request->get('data_da');
        $dataA = $request->get('data_a');

        if (empty($sortByName)) {
            $sortByName = 'pr_movements.data_documento';
            $orderBy = 'desc';
        }

        $is_approver = $this->approver();

        $objs = DB::table('pr_movements')
            ->select('pr_movements.*', 'wf_user_approvals.approval_action')
            ->leftJoin('wf_user_approvals', function ($join) {
                $join->on('pr_movements.id', '=', 'wf_user_approvals.model_id');
                $join->where('wf_user_approvals.user_id', '=', Auth::id());
                $join->where('wf_user_approvals.model', '=', PrMovement::$modelName);
            })
            ->whereIn('pr_movements.tipo_movimento', PrMovement::$tipologieApprovabili)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('pr_movements.materiale', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($documentoBy) {
                if ($documentoBy)
                    $query->Where('pr_movements.documento_materiale', 'LIKE', '%' . $documentoBy . '%');
            })
            ->Where(function ($query) use ($tipoBy) {
                if ($tipoBy)
                    $query->Where('pr_movements.tipo_movimento', $tipoBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy === 'pending')
                    $query->WhereNull('pr_movements.stato');
                elseif ($statoBy)
                    $query->Where('pr_movements.stato', $statoBy);
            })
            ->Where(function ($query) use ($dataDa) {
                if ($dataDa)
                    $query->Where('pr_movements.data_documento', '>=', $dataDa);
            })
            ->Where(function ($query) use ($dataA) {
                if ($dataA)
                    $query->Where('pr_movements.data_documento', '<=', $dataA);
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json([
            'objs' => $objs,
            'is_approver' => !empty($is_approver->id),
            'role_id' => $is_approver->role_id ?? null,
        ]);
    }

    public function approval(Request $request)
    {
        $obj = PrMovement::find($request->id);

        $check = $this->checkApprovable($obj);
        if ($check !== true)
            return $check;

        $is_approver = $this->approver();
        if (empty($is_approver->id))
            return $this->denied();

        $completed = WfUserApproval::approval($obj->id, PrMovement::$modelName, Auth::id(), $is_approver->role_id, 'Approved', $request->comment);

        if (is_null($completed))
            return response()->json([
                'success' => false,
                'message' => 'Hai già processato questo movimento',
                'color' => 'warning',
            ]);

        if ($completed)
            DB::table('pr_movements')
                ->where('id', $obj->id)
                ->update(['stato' => 'Approved', 'data_approvazione' => date('Y-m-d')]);
        else
            DB::table('pr_movements')
                ->where('id', $obj->id)
                ->update(['stato' => 'In-Approval']);

        return response()->json([
            'success' => true,
            'message' => 'Movimento Approvato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    public function reject(Request $request)
    {
        $obj = PrMovement::find($request->id);

        $check = $this->checkApprovable($obj);
        if ($check !== true)
            return $check;

        $is_approver = $this->approver();
        if (empty($is_approver->id))
            return $this->denied();

        $recorded = WfUserApproval::approval($obj->id, PrMovement::$modelName, Auth::id(), $is_approver->role_id, 'Rejected', $request->comment);

        if (is_null($recorded))
            return response()->json([
                'success' => false,
                'message' => 'Hai già processato questo movimento',
                'color' => 'warning',
            ]);

        DB::table('pr_movements')
            ->where('id', $obj->id)
            ->update(['stato' => 'Rejected', 'data_approvazione' => date('Y-m-d')]);

        $this->notifyRejected($obj, $request->comment);

        return response()->json([
            'success' => true,
            'message' => 'Movimento Rifiutato',
            'color' => 'success',
            'obj' => $obj,
        ]);
    }

    private function checkApprovable($obj)
    {
        if (empty($obj->id))
            return response()->json([
                'success' => false,
                'message' => 'Movimento non trovato',
                'color' => 'error',
            ]);

        if (!in_array($obj->tipo_movimento, PrMovement::$tipologieApprovabili))
            return response()->json([
                'success' => false,
                'message' => 'Tipologia movimento non approvabile',
                'color' => 'error',
            ]);

        if (in_array($obj->stato, ['Approved', 'Rejected']))
            return response()->json([
                'success' => false,
                'message' => 'Movimento già processato',
                'color' => 'warning',
            ]);

        return true;
    }

    private function denied()
    {
        return response()->json([
            'success' => false,
            'message' => 'Non sei un approvatore per questo movimento',
            'color' => 'error',
        ]);
    }

    private function notifyRejected($obj, $comment = null)
    {
        try {
            $emails = Utility::users_notify(['pr_movement_rejected']);

            if (empty($emails))
                return;

            $info = [
                'approver' => Auth::user()->full_name,
                'comment' => $comment,
            ];

            Mail::send('emails.email_movement_rejected', ['movement' => $obj, 'info' => $info], function ($message) use ($emails, $obj) {
                $message
                    ->to($emails)
                    ->subject('Movimento Magazzino Rifiutato - Doc. ' . $obj->documento_materiale);
            });
        } catch (\Exception $e) {
            Log::error('Errore invio notifica rifiuto movimento ' . $obj->id . ': ' . $e->getMessage());
        }
    }
}
