<?php

namespace App\Http\Controllers;

use App\Models\WfCategory;
use App\Models\WfCertification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WfCertificationController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $certificazioneBy = $request->get('certificazione');
        $disabledBy = $request->get('disabled');

        if (empty($sortByName)) {
            $sortByName = 'certificazione';
            $orderBy = 'asc';
        }

        $objs = WfCertification::select('*')
            ->Where(function ($query) use ($certificazioneBy) {
                if ($certificazioneBy)
                    $query->Where('certificazione', 'LIKE', '%'.$certificazioneBy.'%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function store(Request $request)
    {

        $obj = new WfCertification();
        $obj->certificazione = ucfirst(strtolower($request['certificazione']));
        $obj->disattivo = $request->disattivo;
        $obj->save();

        $message = 'Messaggi.Caetificazione-Aggiunta';

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

        $obj = WfCertification::find($id);
        $obj->certificazione = ucfirst(strtolower($request['certificazione']));
        $obj->disattivo = $request->disattivo;
        $obj->save();

        $message = 'Messaggi.Caetificazione-Modificata';

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
        $objs = DB::table('wf_certifications')->get();

        return response()->json($objs);
    }
}
