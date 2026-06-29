<?php

namespace App\Exports;

use App\Models\HrCompetencyActivity;
use App\Models\HrCompetencyEvaluation;
use App\Models\HrEmployee;
use App\Models\HrRole;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompetencyMatrixExport implements FromCollection, WithHeadings
{
    private $anno;
    private $reparti;

    public function __construct($anno, $reparti)
    {
        $this->anno = $anno;
        $this->reparti = $reparti;
    }

    public function headings(): array
    {
        $employees = $this->getEmployees();
        $activities = $this->getActivities($employees);

        $headings = ['Dipendente'];
        foreach ($activities as $activity) {
            $headings[] = $activity['attivita'];
        }

        return $headings;
    }

    public function collection(): Collection
    {
        $employees = $this->getEmployees();
        $activities = $this->getActivities($employees);
        $evaluations = $this->getEvaluations($employees, $activities);

        $rows = new Collection();

        foreach ($employees as $employee) {
            $row = [$employee->nome_completo];

            foreach ($activities as $activity) {
                $eval = $evaluations->get($employee->id . '_' . $activity['id']);
                $row[] = $eval ? $this->valutazioneLabel((int) $eval->valutazione) : '-';
            }

            $rows->push($row);
        }

        return $rows;
    }

    private function getEmployees()
    {
        $query = HrEmployee::where('dimesso', false)
            ->orderBy('nome_completo', 'asc');

        if ($this->reparti) {
            $repartiIds = is_array($this->reparti) ? $this->reparti : explode(',', $this->reparti);
            $query->whereIn('reparto_id', $repartiIds);
        }

        return $query->get();
    }

    private function getActivities($employees)
    {
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

        $activityColumns = [];
        foreach ($activities as $activity) {
            if ($activity->roles->isEmpty()) {
                continue;
            }

            $activityColumns[] = [
                'id' => $activity->id,
                'attivita' => $activity->attivita,
            ];
        }

        return $activityColumns;
    }

    private function getEvaluations($employees, $activities)
    {
        $employeeIds = $employees->pluck('id')->toArray();
        $activityIds = collect($activities)->pluck('id')->toArray();

        if (empty($employeeIds) || empty($activityIds)) {
            return collect();
        }

        return HrCompetencyEvaluation::whereIn('employee_id', $employeeIds)
            ->whereIn('activity_id', $activityIds)
            ->where('anno', $this->anno)
            ->get()
            ->keyBy(function ($item) {
                return $item->employee_id . '_' . $item->activity_id;
            });
    }

    private function valutazioneLabel(int $val): string
    {
        $labels = ['Non richiesta', 'Insufficiente', 'Sufficiente', 'Buona', 'Ottima'];
        return $labels[$val] ?? '-';
    }
}
