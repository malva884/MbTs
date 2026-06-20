<?php

namespace App\Http\Controllers;

use App\Models\WfOffice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WfOfficeController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $ufficioBy = $request->get('ufficio');
        $disabledBy = $request->get('disabled');

        if (empty($sortByName)) {
            $sortByName = 'ufficio';
            $orderBy = 'asc';
        }

        $objs = WfOffice::select('*')
            ->Where(function ($query) use ($ufficioBy) {
                if ($ufficioBy)
                    $query->Where('ufficio', 'LIKE', '%'.$ufficioBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new WfOffice();
        $obj->ufficio = strtoupper($request['ufficio']);
        $obj->descrizione = $request['descrizione'];
        $obj->disattivo = $request->disattivo;
        $obj->save();

        $message = 'Messaggi.Ufficio-Aggiunta';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function update(Request $request, $id)
    {

        $obj = WfOffice::find($id);
        $obj->ufficio = strtoupper($request['ufficio']);
        $obj->descrizione = $request['descrizione'];
        $obj->disattivo = $request->disattivo;
        $obj->save();

        $message = 'Messaggi.Ufficio-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message ,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function get_list(Request $request)
    {
        $objs = DB::table('wf_offices')->get();

        return response()->json($objs);
    }
}
