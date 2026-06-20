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
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Termwind\Components\Hr;

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
       $ems= DB::connection('mysql_old')->table('employees')
            ->select('employees.*','department_jobs.department')
            ->join('department_jobs','employees.department','department_jobs.id')
            ->get();
       foreach ($ems as $em){
            $o = HrEmployee::where('matricola',$em->matricola)->first();
            if(empty($o->id))
                $o = new HrEmployee();

            $r = HrDepartment::where('reparto',$em->department)->first();
            if(empty($r->id)){
                $r = new HrDepartment();
                $r->Reparto = $em->department;
                $r->save();
            }
            $c = HrCostCenter::where('valore',$em->centro)->first();
            if(empty($c->id)){
                $c = new HrCostCenter();
                $c->centro_di_costo = ucwords(str_replace("_"," ", $em->centro));
                $c->valore = $em->centro;
                $c->disattivo = false;
                $c->save();
            }

            $o->nome = $em->nome;
            $o->cognome = $em->cognome;
            $o->nome_completo = $em->nome.' '.$em->cognome;
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


        }

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

    public function store(Request $request)
    {
        $obj = new HrEmployee();
        $obj->nome = ucfirst(strtolower($request->nome));
        $obj->cognome = ucfirst(strtolower($request->cognome));
        $obj->nome_completo = $obj->cognome.' '.$obj->nome;
        $obj->email = $request->email;
        $obj->matricola = $request->matricola;
        $obj->data_assunzione = $request->data_assunzione;
        $obj->data_nascita = $request->data_nascita;
        $obj->tel = $request->tel;
        $obj->tel_az = $request->tel_az;
        $obj->valutatore = $request->valutatore;
        //$obj->ruolo_id = 1;//$request->ruolo_id;
        $obj->reparto_id = $request->reparto_id;
        $obj->centro_id = $request->centro_id;
        $obj->sesso = $request->sesso;
        $obj->data_ultima_visita = $request->data_ultima_visita;
        $obj->numero_anni_visita_medica = $request->numero_anni_visita_medica;
        if($obj->data_ultima_visita && $obj->numero_anni_visita_medica)
            $obj->data_scadenza_visita = date('Y-m-d', strtotime(date('Y-m-d', strtotime($obj->data_ultima_visita)) . " +".$obj->numero_anni_visita_medica." years"));
        if ($obj->sesso == 'm')
            $obj->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
        else
            $obj->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';

        $obj->path_drive = GoogleDrive::add_folder(['1LQ8Pw4zkaRqfHbEdb98IJOQ_ndj6x9hl'], $obj->matricola . ' ( ' . $obj->nome_completo.' )', 'google', false);
        $obj->company_id = $request->company_id;
        $obj->save();

        // avvio la creazione delle formazioni obligatorie
        dispatch(new HrCreazioneFormazioniObligatorie($obj->id,Auth::id()));

        // avvio la creazione delle formazioni obligatorie
        dispatch(new EmployeeSyncPortal($obj->id));

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Dipendente-Aggiunto',
                'color' => 'success',
                'obj' => $obj,
            ]
        );
    }

    public function update(Request $request, $id)
    {

        $editDrive = false;
        $obj = HrEmployee::find($id);
        if( $obj->nome != $request->nome || $obj->cognome != $request->cognome || $obj->matricola != $request->matricola)
            $editDrive = true;

        $obj->nome = $request->nome;
        $obj->cognome = $request->cognome;
        $obj->email = $request->email;
        $obj->matricola = $request->matricola;
        $obj->data_assunzione = $request->data_assunzione;
        $obj->data_nascita = $request->data_nascita;
        $obj->tel = $request->tel;
        $obj->tel_az = $request->tel_az;
        $obj->ruolo_id = $request->ruolo_id;
        $obj->reparto_id = $request->reparto_id;
        $obj->centro_id = $request->centro_id;
        $obj->sesso = $request->sesso;
        $obj->dimesso = ($request->dimesso === true ? true:false);
        $obj->valutatore = ($request->valutatore === true ? true:false);
        if ($obj->sesso == 'm')
            $obj->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
        else
            $obj->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
        $obj->save();

       // if($editDrive)
          //  Dispatch(new EmployeeDriver($obj->id));

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Dipendente-Modificato',
                'color' => 'success'
            ]
        );
    }

    public function view($id)
    {
        $obj = HrEmployee::find($id);

        return $obj;
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
}
