<?php

namespace App\Http\Controllers;

use App\Jobs\RichiesteGiorniDipendenti;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\HrRequestPending;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HrHoursRequestedController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $matricolaBy = $request->get('matricola');
        $dipendenteBy = $request->get('dipendente');
        $tipologiaBy = $request->get('tipologia');
        $statoApprovazioneBy = $request->get('statoApprovazione');


        if (empty($sortByName)) {
            $sortByName = 'bacheca_id';
            $orderBy = 'desc';
        }

        if(Auth::user()->hasPermissionTo('hr.richieste.admin') || Auth::user()->role == 'super admin'){
            $objs = HrHoursRequested::select('hr_hours_requesteds.*')
                ->Where(function ($query) use ($dipendenteBy) {
                    if ($dipendenteBy)
                        $query->Where('dipendente_cognome', 'LIKE', '%' . $dipendenteBy . '%')
                            ->orWhere('dipendente_nome','LIKE', '%' . $dipendenteBy . '%');
                })
                ->Where(function ($query) use ($matricolaBy) {
                    if ($matricolaBy)
                        $query->Where('hr_hours_requesteds.dipendente_matricola', 'LIKE', '%' . $matricolaBy . '%');
                })
                ->Where(function ($query) use ($tipologiaBy) {
                    if ($tipologiaBy)
                        $query->Where('tipologia', $tipologiaBy);
                })
                ->Where(function ($query) use ($statoApprovazioneBy) {
                    if ($statoApprovazioneBy == '100')
                        $query->WhereNull('stato');
                    elseif ($statoApprovazioneBy)
                        $query->Where('stato', $statoApprovazioneBy);
                })
                ->orderBy($sortByName, $orderBy)
                ->paginate($request->itemsPerPage);

        }
        else{
            $objs = HrHoursRequested::select('hr_hours_requesteds.*')
                ->join('hr_request_pendings','hr_request_pendings.richiesta_id','hr_hours_requesteds.id')
                //where('company_id',auth()->user()->company_id)
                ->where('hr_request_pendings.user_id',Auth::id())
                ->Where(function ($query) use ($dipendenteBy) {
                    if ($dipendenteBy)
                        $query->Where('dipendente_cognome', 'LIKE', '%' . $dipendenteBy . '%')
                            ->orWhere('dipendente_nome','LIKE', '%' . $dipendenteBy . '%');
                })
                ->Where(function ($query) use ($matricolaBy) {
                    if ($matricolaBy)
                        $query->Where('hr_hours_requesteds.dipendente_matricola', 'LIKE', '%' . $matricolaBy . '%');
                })
                ->Where(function ($query) use ($tipologiaBy) {
                    if ($tipologiaBy)
                        $query->Where('tipologia', $tipologiaBy);
                })
                ->Where(function ($query) use ($statoApprovazioneBy) {
                    if ($statoApprovazioneBy == '100')
                        $query->WhereNull('hr_request_pendings.stato');
                    elseif ($statoApprovazioneBy)
                        $query->Where('hr_request_pendings.stato', $statoApprovazioneBy);
                })
                ->orderBy($sortByName, $orderBy)
                ->paginate($request->itemsPerPage);
        }

        return response()->json($objs);
    }

    public function view($id)
	{				
		// 1. Controllo permessi approvazione
		if(Auth::user()->role == 'super admin') {
			$pending = HrRequestPending::where('richiesta_id', $id)
				->whereNull('stato')
				->orderBy('livello', 'desc')
				->first();
		} else {   
			$pending = HrRequestPending::where('richiesta_id', $id)
				->where('user_id', Auth::id())
				->whereNull('stato')
				->orderBy('livello', 'desc')
				->first();
		}

		$stato = !empty($pending->id);

		// 2. Recupero richiesta e dettagli
		$richiesta = HrHoursRequested::where('id', $id)->first();
		$objs = HrHoursRequestedDetail::where('richiesta_id', $id)
			->orderBy('data', 'asc')
			->get();

		// ==========================================
		// INTERCETTAZIONE E LOGGING DELL'ID VUOTO
		// ==========================================
		if ($objs->isEmpty()) {
			// Scrive nel file storage/logs/laravel.log l'ID esatto della richiesta "vuota"
			Log::warning("La richiesta HR ID: {$id} non ha dettagli associati (HrHoursRequestedDetail).");

			return response()->json([
				'approvazione' => $stato,
				'richiesta'    => $richiesta,
				'data'         => [],
				'Disableds'    => [],
				'holidays'     => [],
				'objs'         => []
			]);
		}

		// 3. Elaborazione Giorni e Festività
		$days = [];
		$holidays = [];
		foreach ($objs as $obj) {
			$d_temp = explode("-", $obj->data);
			$t = substr($d_temp[2], 0, 1);
			if ($t == 0) {
				$t = substr($d_temp[2], 1, 1);
				$ho = $d_temp[1] . '-' . $t;
				$days[] = $d_temp[0] . '-' . $d_temp[1] . '-' . $t;
			} else {
				$ho = $d_temp[1] . '-' . $d_temp[2];
				$days[] = $obj->data;
			}

			if ($obj->tipologia == 3 || $obj->tipologia == 4) {
				$holidays[$ho] = '-----';
			}
		}

		// 4. Calcolo Calendari (Sicuro perché ora sappiamo che $first non è null)
		$first = $objs->first();
		$anno = date('Y', strtotime($first->data));
		$mese = date('m', strtotime($first->data));
		
		$calUno     = date('Y-m', strtotime("+0 months", strtotime($anno.'-'.$mese.'-1')));
		$calDue     = date('Y-m', strtotime("+1 months", strtotime($anno.'-'.$mese.'-1')));
		$calTre     = date('Y-m', strtotime("+2 months", strtotime($anno.'-'.$mese.'-1')));
		$calQuatro  = date('Y-m', strtotime("+3 months", strtotime($anno.'-'.$mese.'-1')));
		$calCinque  = date('Y-m', strtotime("+4 months", strtotime($anno.'-'.$mese.'-1')));
		$calSei     = date('Y-m', strtotime("+5 months", strtotime($anno.'-'.$mese.'-1')));
		$calSette   = date('Y-m', strtotime("+6 months", strtotime($anno.'-'.$mese.'-1')));
		$calOtto    = date('Y-m', strtotime("+7 months", strtotime($anno.'-'.$mese.'-1')));
		$calNove    = date('Y-m', strtotime("+8 months", strtotime($anno.'-'.$mese.'-1')));
		$calDieci   = date('Y-m', strtotime("+9 months", strtotime($anno.'-'.$mese.'-1')));
		$calUndici  = date('Y-m', strtotime("+10 months", strtotime($anno.'-'.$mese.'-1')));
		$calDodici  = date('Y-m', strtotime("+11 months", strtotime($anno.'-'.$mese.'-1')));

		// 5. Giorni Disabilitati
		$giorniDisabilitati = [];
		foreach ([$calUno, $calDue, $calTre] as $calendaro) {
			for ($i = 1; $i <= 31; $i++) {
				$giorniDisabilitati[] = $calendaro . '-' . $i;
			}
		}

		return response()->json([
			'approvazione' => $stato, 
			'richiesta'    => $richiesta, 
			'data'         => $days,
			'CalUno'       => $calUno, 
			'CalDue'       => $calDue, 
			'CalTre'       => $calTre,
			'CalQuattro'   => $calQuatro, 
			'CalCinque'    => $calCinque, 
			'CalSei'       => $calSei,
			'CalSette'     => $calSette, 
			'CalOtto'      => $calOtto, 
			'CalNove'      => $calNove,
			'CalDieci'     => $calDieci, 
			'CalUndici'    => $calUndici, 
			'CalDodici'    => $calDodici,
			'Disableds'    => $giorniDisabilitati, 
			'holidays'     => $holidays, 
			'objs'         => $objs 
		]);
	}

    public function log($id)
    {
		$objs = DB::select("select b.approvatore, b.stato from hr_hours_requesteds as a join hr_request_pendings as b on a.id = b.richiesta_id where a.id = '".$id."' AND b.user_id IN (select user_id from hr_approver_requests where centro_ci_costo = a.centro_di_costo
				AND livello IN (select livello from hr_request_pendings where richiesta_id = '".$id."' AND notifica = 1)
				) group by b.approvatore, b.stato");	

        return response()->json($objs);
    }

    public function save(Request $request, $id)
    {
        Log::info('Richiesta: '.$id.' Utente AP.:'.Auth::id().' Stato: '.$request->esito);
        //$pending = HrRequestPending::where('richiesta_id',$id)->where('user_id',Auth::id())->first();
        $pending = HrRequestPending::where('richiesta_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        // Se non trovato e l'utente è 7 (admin), crea un nuovo record per l'utente 7
        if (!$pending && Auth::id() == 7) {
            // Recupera il livello più alto esistente per questa richiesta
            $maxLivello = HrRequestPending::where('richiesta_id', $id)->max('livello') ?? 1;

            $pending = new HrRequestPending();
            $pending->richiesta_id = $id;
            $pending->user_id = 7;
            $pending->livello = $maxLivello;
            Log::info('Creato nuovo HrRequestPending per admin (user 7) e richiesta '.$id.' con livello '.$maxLivello);
        }

        if (!$pending) {
            Log::error('Nessun HrRequestPending trovato per richiesta '.$id.' e utente '.Auth::id());
            throw new \Exception('Record pending non trovato');
        }

        $pending->stato = $request->esito;
        $pending->approvatore = Auth::user()->full_name;
        if(!empty($request->nota))
            $pending->nota = $request->nota;
        if($pending->save()){
            DB::table("hr_request_pendings")
                ->where('richiesta_id',$id)
                ->whereNull('stato')
                ->update(['approvatore' => Auth::user()->full_name, 'stato' => $request->esito]);

            try {

                $this->setRichiestaRicevuta($pending->bacheca_id,'yj0vivtUkJWe8DA2Ol');

            } catch (\Exception $e) {


            }

            dispatch(new RichiesteGiorniDipendenti($id, Auth::id()));
        }
    }
	
	public function all()
    {
		$centri = DB::table('hr_approver_requests')->select('centro_ci_costo')
                ->where('user_id',Auth::id())
				->pluck('centro_ci_costo');
                //->first();
		
		
        $objs = DB::connection('mysql_old')->table('employees_attendances')
                ->join('employees','employees.id','employees_attendances.employee')
                ->select('start_date as data')
				->whereIn('centro', $centri)
                ->whereNotIn('type',['0'])
                ->whereYear('start_date','>=',date('Y'))
                ->whereMonth('start_date','>=', 1)
                ->where('resigned',0)
                ->orderBy('start_date','asc')
                ->groupBy('start_date')
                ->get();
		
		$obj2 = DB::table('hr_hours_requested_details')->select('hr_hours_requested_details.data')
            ->join('hr_hours_requesteds','hr_hours_requested_details.richiesta_id','hr_hours_requesteds.id')
            ->Where(function ($query) use ($centri) {
                if (!empty($centri))
                    $query->WhereIn('centro_di_costo',  $centri);
            })
            ->whereNotIn('hr_hours_requesteds.bacheca_id',
                DB::table('hr_hours_requested_details')->select('bacheca_id')
                    ->whereYear('data','>=',date('Y'))
                    ->whereMonth('data','>=', 1)
                    ->whereIn('tipologia',[105])
                    ->where('confermato',true)
            )
            ->whereIn('hr_hours_requesteds.tipologia',[5])
			->where('hr_hours_requesteds.stato',1)
            ->whereYear('data','>=',date('Y'))
            ->whereMonth('data','>=', 1)
            ->orderBy('data','asc')
            ->groupBy('data','hr_hours_requesteds.bacheca_id')
            ->get();

        $days = [];
        $holidays = [];
        foreach ($objs as $obj){
            $d_temp = explode("-",$obj->data);
            $t = substr($d_temp[2], 0, 1);
            $m = substr($d_temp[1], 0, 1);
            $ms = substr($d_temp[1], 1, 1);
            if($t == 0){
                $t = substr($d_temp[2], 1, 1);
                $holidays[($m == 0 ? $ms:$d_temp[1]).'-'.$t] = '__';
                $days[] = $d_temp[0].'-'.$d_temp[1].'-'.$t;
            }
            else{
                $d_temp = explode("-",$obj->data);
                $holidays[($m == 0 ? $ms:$d_temp[1]).'-'.$d_temp[2]] = '__';
                $days[] = $obj->data;
            }
        }
		
		foreach ($obj2 as $obj){
            $d_temp = explode("-",$obj->data);
            $t = substr($d_temp[2], 0, 1);
            $m = substr($d_temp[1], 0, 1);
            $ms = substr($d_temp[1], 1, 1);
            if($t == 0){
                $t = substr($d_temp[2], 1, 1);
                $holidays[($m == 0 ? $ms:$d_temp[1]).'-'.$t] = '__';
                $days[] = $d_temp[0].'-'.$d_temp[1].'-'.$t;
            }
            else{
                $d_temp = explode("-",$obj->data);
                $holidays[($m == 0 ? $ms:$d_temp[1]).'-'.$d_temp[2]] = '__';
                $days[] = $obj->data;
            }
        }

        $first = $objs->first();
        $anno = date('Y', strtotime(@$first->data));
        $mese = date('m', strtotime(@$first->data));
        $calUno =date('Y-m', strtotime("+0 months", strtotime($anno.'-'.$mese.'-1')));
        $calDue =date('Y-m', strtotime("+1 months", strtotime($anno.'-'.$mese.'-1')));
        $calTre =date('Y-m', strtotime("+2 months", strtotime($anno.'-'.$mese.'-1')));
        $calQuatro =date('Y-m', strtotime("+3 months", strtotime($anno.'-'.$mese.'-1')));
        $calCinque =date('Y-m', strtotime("+4 months", strtotime($anno.'-'.$mese.'-1')));
        $calSei =date('Y-m', strtotime("+5 months", strtotime($anno.'-'.$mese.'-1')));
        $calSette =date('Y-m', strtotime("+6 months", strtotime($anno.'-'.$mese.'-1')));
        $calOtto =date('Y-m', strtotime("+7 months", strtotime($anno.'-'.$mese.'-1')));
        $calNove =date('Y-m', strtotime("+8 months", strtotime($anno.'-'.$mese.'-1')));
        $calDieci =date('Y-m', strtotime("+9 months", strtotime($anno.'-'.$mese.'-1')));
        $calUndici =date('Y-m', strtotime("+10 months", strtotime($anno.'-'.$mese.'-1')));
        $calDodici =date('Y-m', strtotime("+11 months", strtotime($anno.'-'.$mese.'-1')));

        $giorniDisabilitati = [];
        foreach ([$calUno,$calDue,$calTre] as $calendaro){
            for($i = 1; $i<= 31; $i++ ){
                $giorniDisabilitati[] = $calendaro.'-'.$i;
            }
        }

        return response()->json([ 'data'=> $days,
            'CalUno'=> $calUno, 'CalDue'=> $calDue, 'CalTre'=> $calTre,
            'CalQuattro'=> $calQuatro, 'CalCinque'=> $calCinque, 'CalSei'=> $calSei,
            'CalSette'=> $calSette, 'CalOtto'=> $calOtto, 'CalNove'=> $calNove,
            'CalDieci'=> $calDieci, 'CalUndici'=> $calUndici, 'CalDodici'=> $calDodici,
            'Disableds' => $giorniDisabilitati, 'holidays' => (object) $holidays, 'objs' => $objs ]);
    }

    public function get_emploee(Request $request)
    {
        $objs = [];
        if(empty($request->date)){
            return response()->json($objs);
        }
        $data = str_replace('"','',$request->date);
        $data = explode("T",$data);
        $dataSelezionata = date('Y-m-d',strtotime($data[0].' +1 days'));
		
		$centri = DB::table('hr_approver_requests')->select('centro_ci_costo')
                //->where('user_id',7)
				->where('user_id',Auth::id())
				//->where('notifica',true)
				->pluck('centro_ci_costo');
				
		$objsUno = DB::table('hr_hours_requested_details')
                ->join('hr_hours_requesteds','hr_hours_requested_details.richiesta_id','hr_hours_requesteds.id')
                ->select('data','hr_hours_requesteds.dipendente_cognome','hr_hours_requesteds.dipendente_nome','hr_hours_requesteds.tipologia','ora_inizio','ora_fine')
                ->whereIn('centro_di_costo', $centri)
				->where('hr_hours_requesteds.stato',1)
                ->whereNotIn('hr_hours_requesteds.bacheca_id',
                    DB::table('hr_hours_requested_details')->select('bacheca_id')
                        ->where('data','=',$dataSelezionata)
                        ->whereIn('tipologia',[101,102,105])
                        ->where('confermato',true)
                )
                ->where('data','=',$dataSelezionata)
                ->orderBy('dipendente_cognome','asc')
                ->get();

            $objsDue = DB::connection('mysql_old')->table('employees_attendances')
                ->join('employees','employees.id','employees_attendances.employee')
                ->select('start_date as data','cognome as dipendente_cognome','nome as dipendente_nome','type as tipologia')
                ->whereIn('centro', $centri)
                ->whereIn('type',['2'])
                ->where('start_date','=',$dataSelezionata)
                ->where('resigned',0)
                ->orderBy('cognome','asc')
                ->get();		
/*
        if(Auth::user()->hasPermissionTo('hr.richieste.admin')){
            $objsUno = DB::table('hr_hours_requested_details')
                ->join('hr_hours_requesteds','hr_hours_requested_details.richiesta_id','hr_hours_requesteds.id')
                ->select('data','hr_hours_requesteds.dipendente_cognome','hr_hours_requesteds.dipendente_nome','hr_hours_requesteds.tipologia','ora_inizio','ora_fine')
                ->whereNotIn('hr_hours_requesteds.bacheca_id',
                    DB::table('hr_hours_requested_details')->select('bacheca_id')
                        ->where('data','=',$dataSelezionata)
                        ->whereIn('tipologia',[101,102,105])
                        ->where('confermato',true)
                )
                ->where('data','=',$dataSelezionata)
                ->orderBy('dipendente_cognome','asc')
                ->get();

            $objsDue = DB::connection('mysql_old')->table('employees_attendances')
                ->join('employees','employees.id','employees_attendances.employee')
                ->select('start_date as data','cognome as dipendente_cognome','nome as dipendente_nome','type as tipologia')
                ->whereIn('type',['2'])
                ->where('start_date','=',$dataSelezionata)
                ->where('resigned',0)
                ->orderBy('cognome','asc')
                ->get();

        }else{
            $centro = DB::table('hr_approver_requests')->select('centro_ci_costo')
                ->where('user_id',Auth::id())
                ->first();

            $objsUno = DB::table('hr_hours_requested_details')
                ->join('hr_hours_requesteds','hr_hours_requested_details.richiesta_id','hr_hours_requesteds.id')
                ->select('data','hr_hours_requesteds.dipendente_cognome','hr_hours_requesteds.dipendente_nome','hr_hours_requesteds.tipologia','ora_inizio','ora_fine')
                ->whereIn('centro_di_costo', $centri)
				->where('hr_hours_requesteds.stato',1)
                ->whereNotIn('hr_hours_requesteds.bacheca_id',
                    DB::table('hr_hours_requested_details')->select('bacheca_id')
                        ->where('data','=',$dataSelezionata)
                        ->whereIn('tipologia',[101,102,105])
                        ->where('confermato',true)
                )
                ->where('data','=',$dataSelezionata)
                ->orderBy('dipendente_cognome','asc')
                ->get();

            $objsDue = DB::connection('mysql_old')->table('employees_attendances')
                ->join('employees','employees.id','employees_attendances.employee')
                ->select('start_date as data','cognome as dipendente_cognome','nome as dipendente_nome','type as tipologia')
                ->whereIn('centro', $centri)
                ->whereIn('type',['2'])
                ->where('start_date','=',$dataSelezionata)
                ->where('resigned',0)
                ->orderBy('cognome','asc')
                ->get();
        }
*/
        foreach ($objsUno as $item)
            $objs[$item->dipendente_cognome] = ['data' => $item->data, 'dipendente_cognome' => $item->dipendente_cognome, 'dipendente_nome' => $item->dipendente_nome, 'tipologia' => $item->tipologia, 'ora_fine' => $item->ora_fine, 'ora_inizio' => $item->ora_inizio];

        foreach ($objsDue as $item)
            $objs[$item->dipendente_cognome] = ['data' => $item->data, 'dipendente_cognome' => $item->dipendente_cognome, 'dipendente_nome' => $item->dipendente_nome, 'tipologia' => '6'];

        ksort($objs);

        return response()->json($objs);
    }
	
	private function setRichiestaRicevuta($id, $token)
    {
        $path = 'https://app.metallurgicabresciana.it/turni/mb/richieste/api/set_approvazione.php?';
        $path .= 'tk=' . $token;
        $path .= '&id='. $id;
        $path .= '&inviata=1';
        $getMovieList = file_get_contents($path);
    }
}
