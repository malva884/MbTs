<?php

namespace App\Http\Controllers;

use App\Exports\CompetencyMatrixExport;
use App\Models\HrCompetencyActivity;
use App\Models\HrCompetencyEvaluation;
use App\Models\HrEmployee;
use App\Models\HrRole;
use App\Models\Utility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class HrCompetencyEvaluationController extends Controller
{
    public function activitiesList(Request $request)
    {
        $query = HrCompetencyActivity::with('roles');

        if ($request->filled('disattivo')) {
            $query->where('disattivo', $request->disattivo == 1);
        } else {
            $query->where('disattivo', false);
        }

        if ($request->filled('search')) {
            $query->where('attivita', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('hr_role_id')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('hr_roles.id', $request->hr_role_id);
            });
        }

        $objs = $query->orderBy('attivita', 'asc')
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    public function activitiesByRole($roleId)
    {
        $role = HrRole::find($roleId);
        if (!$role) {
            return response()->json([]);
        }

        return response()->json(
            $role->activities()
                ->where('disattivo', false)
                ->orderBy('attivita', 'asc')
                ->get()
        );
    }

    public function activityStore(Request $request)
    {
        $request->validate([
            'attivita' => 'required|string',
        ]);

        $existingActivity = HrCompetencyActivity::where('attivita', $request->attivita)
            ->where('disattivo', false)
            ->first();

        if ($existingActivity) {
            if ($request->filled('roles')) {
                $sync = [];
                foreach ($request->roles as $r) {
                    $sync[$r['hr_role_id']] = ['valutazione_ideale' => $r['valutazione_ideale'] ?? 0];
                }
                $existingActivity->roles()->syncWithoutDetaching($sync);
            }

            return response()->json([
                'success' => true,
                'message' => 'Messaggi.Attivita-Modificata',
                'color' => 'success',
                'obj' => $existingActivity->load('roles')
            ]);
        }

        $obj = new HrCompetencyActivity();
        $obj->attivita = $request->attivita;
        $obj->disattivo = false;
        $obj->save();

        if ($request->filled('roles')) {
            $sync = [];
            foreach ($request->roles as $r) {
                $sync[$r['hr_role_id']] = ['valutazione_ideale' => $r['valutazione_ideale'] ?? 0];
            }
            $obj->roles()->sync($sync);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Attivita-Salvata',
            'color' => 'success',
            'obj' => $obj->load('roles')
        ]);
    }

    public function activityUpdate(Request $request, $id)
    {
        $request->validate([
            'attivita' => 'required|string',
        ]);

        $obj = HrCompetencyActivity::find($id);
        if (!$obj) {
            return response()->json([
                'success' => false,
                'message' => 'Attivita non trovata',
                'color' => 'error'
            ], 404);
        }

        if ($obj->attivita !== $request->attivita) {
            $existingActivity = HrCompetencyActivity::where('attivita', $request->attivita)
                ->where('disattivo', false)
                ->where('id', '!=', $id)
                ->first();

            if ($existingActivity) {
                if ($request->filled('roles')) {
                    $sync = [];
                    foreach ($request->roles as $r) {
                        $sync[$r['hr_role_id']] = ['valutazione_ideale' => $r['valutazione_ideale'] ?? 0];
                    }
                    $existingActivity->roles()->sync($sync);
                }

                $obj->disattivo = true;
                $obj->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Messaggi.Attivita-Modificata',
                    'color' => 'success',
                    'obj' => $existingActivity->load('roles')
                ]);
            }
        }

        $obj->attivita = $request->attivita;
        $obj->disattivo = $request->disattivo ? true : false;
        $obj->save();

        if ($request->filled('roles')) {
            $sync = [];
            foreach ($request->roles as $r) {
                $sync[$r['hr_role_id']] = ['valutazione_ideale' => $r['valutazione_ideale'] ?? 0];
            }
            $obj->roles()->sync($sync);
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Attivita-Modificata',
            'color' => 'success',
            'obj' => $obj->load('roles')
        ]);
    }

    public function activityDestroy($id)
    {
        $obj = HrCompetencyActivity::find($id);
        if ($obj) {
            $obj->disattivo = true;
            $obj->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Attivita-Eliminata',
            'color' => 'success',
            'obj' => null
        ]);
    }

    public function evaluationsList(Request $request)
    {
        $query = HrCompetencyEvaluation::with(['employee', 'activity.role']);

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('anno')) {
            $query->where('anno', $request->anno);
        } else {
            $query->where('anno', date('Y'));
        }

        if ($request->filled('valutatore_id')) {
            $query->where('valutatore_id', $request->valutatore_id);
        }

        $objs = $query->orderBy('created_at', 'desc')
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    public function evaluationsByEmployee($employeeId, Request $request)
    {
        $anno = $request->get('anno', date('Y'));

        $evaluations = HrCompetencyEvaluation::with(['activity', 'valutatore'])
            ->where('employee_id', $employeeId)
            ->where('anno', $anno)
            ->get()
            ->keyBy('activity_id');

        $employee = HrEmployee::with(['roles' => function ($q) {
            $q->where('disattivo', false);
        }])->find($employeeId);

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Dipendente non trovato',
                'color' => 'error'
            ], 404);
        }

        $roleIds = $employee->roles->pluck('id')->toArray();
        $evaluatedActivityIds = $evaluations->keys()->toArray();

        $allActivities = HrCompetencyActivity::where(function ($q) use ($roleIds, $evaluatedActivityIds) {
            $q->whereHas('roles', function ($q2) use ($roleIds) {
                $q2->whereIn('hr_roles.id', $roleIds);
            });
            if (!empty($evaluatedActivityIds)) {
                $q->orWhereIn('id', $evaluatedActivityIds);
            }
        })
            ->with(['roles' => function ($q) {
                $q->withPivot('valutazione_ideale');
            }])
            ->where('disattivo', false)
            ->get()
            ->map(function ($activity) use ($roleIds) {
                $matchingRole = $activity->roles->first(function ($r) use ($roleIds) {
                    return in_array($r->id, $roleIds);
                });
                $role = $matchingRole ?: $activity->roles->first();
                $activity->valutazione_ideale = $role ? ($role->pivot->valutazione_ideale ?? 0) : 0;
                $activity->ruolo = $role ? $role->ruolo : '';
                $activity->tipo = $role ? $role->tipo : '';
                return $activity;
            });

        $activities = collect();
        $seenActivityIds = collect();

        foreach ($allActivities as $activity) {
            if ($seenActivityIds->contains($activity->id)) continue;
            $seenActivityIds->push($activity->id);

            $eval = $evaluations->get($activity->id);

            $activities->push([
                'activity_id' => $activity->id,
                'attivita' => $activity->attivita,
                'valutazione_ideale' => (int) $activity->valutazione_ideale,
                'ruolo' => $activity->ruolo,
                'tipo' => $activity->tipo,
                'valutazione' => $eval ? (int) $eval->valutazione : null,
                'data_valutazione' => $eval ? $eval->data_valutazione : null,
                'created_at' => $eval ? $eval->created_at : null,
                'valutatore' => $eval && $eval->valutatore ? $eval->valutatore->full_name : null,
                'note' => $eval ? $eval->note : null,
                'evaluation_id' => $eval ? $eval->id : null,
            ]);
        }

        return response()->json([
            'employee' => $employee,
            'anno' => (int)$anno,
            'activities' => $activities,
        ]);
    }

    public function evaluationStore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'activity_id' => 'required|exists:hr_competency_activities,id',
            'valutazione' => 'required|integer|min:0|max:4',
            'anno' => 'required|integer',
            'note' => 'nullable|string',
        ]);

        $anno = $request->anno ?? date('Y');

        $obj = HrCompetencyEvaluation::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'activity_id' => $request->activity_id,
                'anno' => $anno,
            ],
            [
                'valutazione' => $request->valutazione,
                'valutatore_id' => Auth::id(),
                'data_valutazione' => date('Y-m-d'),
                'note' => $request->note,
            ]
        );

        if (!$obj->wasRecentlyCreated && $this->isExpiredOrExpiring($obj->created_at)) {
            $obj->created_at = now();
            $obj->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Valutazione-Salvata',
            'color' => 'success',
            'obj' => $obj
        ]);
    }

    public function evaluationBulkStore(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:hr_employees,id',
            'anno' => 'required|integer',
            'evaluations' => 'required|array',
            'evaluations.*.activity_id' => 'required|exists:hr_competency_activities,id',
            'evaluations.*.valutazione' => 'required|integer|min:0|max:4',
            'evaluations.*.note' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $anno = $request->anno;

        foreach ($request->evaluations as $eval) {
            $obj = HrCompetencyEvaluation::updateOrCreate(
                [
                    'employee_id' => $request->employee_id,
                    'activity_id' => $eval['activity_id'],
                    'anno' => $anno,
                ],
                [
                    'valutazione' => $eval['valutazione'],
                    'valutatore_id' => $userId,
                    'data_valutazione' => date('Y-m-d'),
                    'note' => $eval['note'] ?? null,
                ]
            );

            if (!$obj->wasRecentlyCreated && $this->isExpiredOrExpiring($obj->created_at)) {
                $obj->created_at = now();
                $obj->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Valutazioni-Salvate',
            'color' => 'success',
        ]);
    }

    public function myTeam(Request $request)
    {
        $user = Auth::user();

        $myEmployee = HrEmployee::where('matricola', $user->matricola)->first();

        if (!$myEmployee) {
            return response()->json([]);
        }

        $employees = HrEmployee::where('reparto_id', $myEmployee->reparto_id)
            ->where('dimesso', false)
            ->where('id', '!=', $myEmployee->id)
            ->with(['roles' => function ($q) {
                $q->where('disattivo', false);
            }, 'department'])
            ->orderBy('nome_completo', 'asc')
            ->get();

        return response()->json($employees);
    }

    public function teamMatrix(Request $request)
    {
        $user = Auth::user();
        $anno = $request->get('anno', date('Y'));

        $myEmployee = HrEmployee::where('matricola', $user->matricola)->first();

        if (!$myEmployee) {
            return response()->json([]);
        }

        $employees = HrEmployee::where('reparto_id', $myEmployee->reparto_id)
            ->where('dimesso', false)
            ->where('id', '!=', $myEmployee->id)
            ->with(['roles' => function ($q) {
                $q->where('disattivo', false);
            }, 'department'])
            ->orderBy('nome_completo', 'asc')
            ->get();

        $roles = HrRole::where('disattivo', false)
            ->orderBy('ruolo', 'asc')
            ->get();

        $roleIds = $roles->pluck('id')->toArray();

        $activities = HrCompetencyActivity::whereHas('roles', function ($q) use ($roleIds) {
            $q->whereIn('hr_roles.id', $roleIds);
        })
            ->with(['roles' => function ($q) use ($roleIds) {
                $q->whereIn('hr_roles.id', $roleIds)->withPivot('valutazione_ideale');
            }])
            ->where('disattivo', false)
            ->orderBy('attivita', 'asc')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();
        $evaluations = HrCompetencyEvaluation::whereIn('employee_id', $employeeIds)
            ->where('anno', $anno)
            ->get()
            ->keyBy(function ($item) {
                return $item->employee_id . '_' . $item->activity_id;
            });

        $matrix = [];
        $activityColumns = [];

        foreach ($activities as $activity) {
            if ($activity->roles->isEmpty()) {
                continue;
            }

            $role = $activity->roles->first();

            $activityColumns[] = [
                'id' => $activity->id,
                'activity_id' => $activity->id,
                'role_ids' => $activity->roles->pluck('id')->toArray(),
                'attivita' => $activity->attivita,
                'ruolo' => $activity->roles->pluck('ruolo')->implode(', '),
                'tipo' => $role->tipo,
                'valutazione_ideale' => (int) ($role->pivot->valutazione_ideale ?? 0),
            ];
        }

        foreach ($employees as $employee) {
            $row = [
                'employee_id' => $employee->id,
                'nome_completo' => $employee->nome_completo,
                'matricola' => $employee->matricola,
                'role_ids' => $employee->roles->pluck('id')->toArray(),
            ];

            foreach ($activityColumns as $activity) {
                $eval = $evaluations->get($employee->id . '_' . $activity['activity_id']);
                $row['activity_' . $activity['id']] = $eval ? (int) $eval->valutazione : null;
                $row['note_' . $activity['id']] = $eval ? $eval->note : '';
            }

            $matrix[] = $row;
        }

        return response()->json([
            'employees' => $employees,
            'activities' => $activityColumns,
            'matrix' => $matrix,
            'anno' => (int) $anno,
        ]);
    }

    public function competencyMatrix(Request $request)
    {
        $anno = $request->get('anno', date('Y'));
        $reparti = $request->get('reparti');

        $employeeQuery = HrEmployee::where('dimesso', false)
            ->with(['roles' => function ($q) {
                $q->where('disattivo', false);
            }, 'department']);

        if ($reparti) {
            $repartiIds = is_array($reparti) ? $reparti : explode(',', $reparti);
            $employeeQuery->whereIn('reparto_id', $repartiIds);
        }

        $employees = $employeeQuery->orderBy('nome_completo', 'asc')->get();

        $roles = HrRole::where('disattivo', false)
            ->orderBy('ruolo', 'asc')
            ->get();

        $roleIds = $roles->pluck('id')->toArray();

        $activities = HrCompetencyActivity::whereHas('roles', function ($q) use ($roleIds) {
            $q->whereIn('hr_roles.id', $roleIds);
        })
            ->with(['roles' => function ($q) use ($roleIds) {
                $q->whereIn('hr_roles.id', $roleIds)->withPivot('valutazione_ideale');
            }])
            ->where('disattivo', false)
            ->orderBy('attivita', 'asc')
            ->get();

        $employeeIds = $employees->pluck('id')->toArray();
        $evaluations = HrCompetencyEvaluation::whereIn('employee_id', $employeeIds)
            ->where('anno', $anno)
            ->get()
            ->keyBy(function ($item) {
                return $item->employee_id . '_' . $item->activity_id;
            });

        $matrix = [];
        $activityColumns = [];

        foreach ($activities as $activity) {
            if ($activity->roles->isEmpty()) {
                continue;
            }

            $role = $activity->roles->first();

            $activityColumns[] = [
                'id' => $activity->id,
                'activity_id' => $activity->id,
                'role_ids' => $activity->roles->pluck('id')->toArray(),
                'attivita' => $activity->attivita,
                'ruolo' => $activity->roles->pluck('ruolo')->implode(', '),
                'tipo' => $role->tipo,
                'valutazione_ideale' => (int) ($role->pivot->valutazione_ideale ?? 0),
            ];
        }

        foreach ($employees as $employee) {
            $row = [
                'employee_id' => $employee->id,
                'nome_completo' => $employee->nome_completo,
                'matricola' => $employee->matricola,
                'role_ids' => $employee->roles->pluck('id')->toArray(),
            ];

            foreach ($activityColumns as $activity) {
                $eval = $evaluations->get($employee->id . '_' . $activity['activity_id']);
                $row['activity_' . $activity['id']] = $eval ? (int) $eval->valutazione : null;
                $row['created_at_' . $activity['id']] = $eval ? $eval->created_at : null;
                $row['note_' . $activity['id']] = $eval ? $eval->note : '';
            }

            $matrix[] = $row;
        }

        return response()->json([
            'employees' => $employees,
            'activities' => $activityColumns,
            'matrix' => $matrix,
            'anno' => (int) $anno,
        ]);
    }

    public function competencyMatrixExport(Request $request)
    {
        $anno = $request->get('anno', date('Y'));
        $reparti = $request->get('reparti');
        $name_file = 'matrice_competenze_' . $anno . '.xlsx';

        $export = new CompetencyMatrixExport($anno, $reparti);
        return Excel::download($export, $name_file);
    }

    public function teamBulkStore(Request $request)
    {
        $request->validate([
            'anno' => 'required|integer',
            'evaluations' => 'required|array',
            'evaluations.*.employee_id' => 'required|exists:hr_employees,id',
            'evaluations.*.activity_id' => 'required|exists:hr_competency_activities,id',
            'evaluations.*.valutazione' => 'required|integer|min:0|max:4',
            'evaluations.*.note' => 'nullable|string',
        ]);

        $userId = Auth::id();
        $anno = $request->anno;

        foreach ($request->evaluations as $eval) {
            HrCompetencyEvaluation::updateOrCreate(
                [
                    'employee_id' => $eval['employee_id'],
                    'activity_id' => $eval['activity_id'],
                    'anno' => $anno,
                ],
                [
                    'valutazione' => $eval['valutazione'],
                    'valutatore_id' => $userId,
                    'data_valutazione' => date('Y-m-d'),
                    'note' => $eval['note'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Valutazioni-Salvate',
            'color' => 'success',
        ]);
    }

    private function isExpiredOrExpiring($createdAt): bool
    {
        if (!$createdAt) return true;
        $expiration = Carbon::parse($createdAt)->addYear();
        return $expiration->lessThanOrEqualTo(now()->addMonth());
    }

    public function expiringReport()
    {
        if (!Auth::user()->hasPermissionTo('hr.dipendenti.report') && Auth::user()->role != 'super admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $today = Carbon::today();
        $threshold = Carbon::today()->addMonths(3);

        // Consider the latest evaluation per employee/activity, using the evaluation date if available
        $latestEvaluations = HrCompetencyEvaluation::query()
            ->select('employee_id', 'activity_id', DB::raw('MAX(ISNULL(data_valutazione, created_at)) as latest_evaluation_date'))
            ->groupBy('employee_id', 'activity_id');

        $items = HrCompetencyEvaluation::query()
            ->select(
                'hr_competency_evaluations.id',
                'hr_competency_evaluations.employee_id',
                'hr_employees.nome_completo',
                'hr_competency_activities.attivita',
                'hr_competency_evaluations.valutazione',
                'hr_competency_evaluations.data_valutazione',
                'hr_competency_evaluations.created_at'
            )
            ->joinSub($latestEvaluations, 'latest', function ($join) {
                $join->on('hr_competency_evaluations.employee_id', '=', 'latest.employee_id')
                    ->on('hr_competency_evaluations.activity_id', '=', 'latest.activity_id')
                    ->on(DB::raw('ISNULL(hr_competency_evaluations.data_valutazione, hr_competency_evaluations.created_at)'), '=', 'latest.latest_evaluation_date');
            })
            ->join('hr_employees', 'hr_employees.id', 'hr_competency_evaluations.employee_id')
            ->join('hr_competency_activities', 'hr_competency_activities.id', 'hr_competency_evaluations.activity_id')
            ->where('hr_employees.dimesso', false)
            ->whereNotNull('hr_competency_evaluations.created_at')
            ->whereRaw('DATEADD(year, 1, ISNULL(hr_competency_evaluations.data_valutazione, hr_competency_evaluations.created_at)) <= ?', [$threshold])
            ->orderByRaw('DATEADD(year, 1, ISNULL(hr_competency_evaluations.data_valutazione, hr_competency_evaluations.created_at))')
            ->get()
            ->map(function ($item) {
                $reference = $item->data_valutazione ?? $item->created_at;
                $expiration = Carbon::parse($reference)->addYear();
                $item->data_scadenza = $expiration->format('Y-m-d');
                $item->days_left = Carbon::today()->diffInDays($expiration, false);
                return $item;
            });

        $expired = $items->filter(function ($item) {
            $reference = $item->data_valutazione ?? $item->created_at;
            return Carbon::parse($reference)->addYear()->isPast();
        })->values();

        $expiring = $items->filter(function ($item) {
            $reference = $item->data_valutazione ?? $item->created_at;
            return !Carbon::parse($reference)->addYear()->isPast();
        })->values();

        return response()->json([
            'expired_count' => $expired->count(),
            'expiring_count' => $expiring->count(),
            'expired' => $expired,
            'expiring' => $expiring,
        ]);
    }
}
