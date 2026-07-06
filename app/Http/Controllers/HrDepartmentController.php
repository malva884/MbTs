<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\HrDepartment;
use App\Models\DipDepartment;

class HrDepartmentController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $repartoBy = $request->get('reparto');
        $disattivoBy = $request->get('disattivo');

        if(empty($sortByName)){
            $sortByName = 'reparto';
            $orderBy = 'asc';
        }

        $query = DB::table('hr_departments')
            ->Where(function ($query) use ($repartoBy) {
                if ($repartoBy)
                    $query->Where('reparto','Like', '%'.$repartoBy.'%');
            });

        if ($disattivoBy !== null && $disattivoBy !== '') {
            $query->where('disattivo', $disattivoBy == 1);
        }

        $objs = $query->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage ?? 10, ['*'], 'page', $request->page ?? 1);

        return response()->json($objs);
    }

    public function getList(Request $request)
    {
        $objs = DB::table('hr_departments')
            ->where('disattivo',false)
            ->orderBy('reparto','asc')
            ->get();

        return response()->json($objs);
    }

    public function store(Request $request)
    {
        $obj = new HrDepartment();
        $obj->reparto = ucwords(strtolower($request->reparto));
        $obj->lavorazione = $request->lavorazione;
        $obj->disattivo = ($request->disattivo ? true : false);
        $obj->save();

        // Sincronizza con il progetto Dipendenti
        $this->syncToDipendentiProject($obj);

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Reparto-Salvato',
            'color' => 'success',
            'obj' => null
        ]);
    }

    public function update(Request $request, $id)
    {
        $obj = HrDepartment::find($id);
        $obj->reparto = ucwords(strtolower($request->reparto));
        $obj->lavorazione = $request->lavorazione;
        $obj->disattivo = ($request->disattivo ? true : false);
        $obj->save();

        // Sincronizza con il progetto Dipendenti
        $this->syncToDipendentiProject($obj);

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Reparto-Modificato',
            'color' => 'success',
            'obj' => null
        ]);
    }

    public function destroy($id)
    {
        $obj = HrDepartment::find($id);
        if ($obj) {
            $obj->disattivo = true;
            $obj->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Messaggi.Reparto-Disattivato',
            'color' => 'success',
            'obj' => null
        ]);
    }

    /**
     * Sincronizza il reparto MbTs con il progetto Dipendenti:
     * crea un department se non esiste già.
     */
    private function syncToDipendentiProject(HrDepartment $department): void
    {
        try {
            // Verifica se esiste già un department con lo stesso department_id
            $existingDepartment = DipDepartment::where('department_id', $department->id)->first();

            if (!$existingDepartment) {
                // Crea il department solo se non esiste
                $dipDepartment = new DipDepartment();
                $dipDepartment->department_name = $department->reparto;
                $dipDepartment->department_id = $department->id;
                $dipDepartment->save();
            }
        } catch (\Exception $e) {
            Log::error("Errore sincronizzazione progetto Dipendenti per reparto {$department->reparto}: " . $e->getMessage());
        }
    }
}
