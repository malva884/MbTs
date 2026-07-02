<?php

namespace App\Http\Controllers;

use App\Exports\Produzione;
use App\Exports\ProduzioneBi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Revolution\Google\Sheets\Facades\Sheets;

class GpController extends Controller
{
    public function getMateriale($ol){
        // recupero il dettaglio del matariale da GP
    /*    $result = DB::connection('sqlsrv_gp')->table('STL_OrdiniMaster_V')->select('STL_OrdiniMaster_V.*','STL_Materiali_V.Descrizione')
            ->join('STL_Materiali_V','STL_Materiali_V.IDProdotto','STL_OrdiniMaster_V.IDProdotto')
            ->where('STL_OrdiniMaster_V.OrdineCliente','=','0000'.$ol)
            ->first();
	*/		
		$result = DB::connection('sqlsrv_gp')->table('AGG_MASTER_TMP')->select('AGG_MASTER_TMP.cdProdotto AS Prodotto','AGG_PRODOTTI_TMP.dsProdotto AS Descrizione','AGG_PRODOTTI_TMP.Conversione')
            ->join('AGG_PRODOTTI_TMP','AGG_PRODOTTI_TMP.cdProdotto','AGG_MASTER_TMP.cdProdotto')
            ->where('AGG_MASTER_TMP.cdOrdine','=',$ol)
            ->first();

		if(empty($result->Prodotto)){
            $result = DB::connection('sqlsrv_root_gp')
                ->table('Produzione as PRD')
                ->select('P.NomeProdotto AS Prodotto','P.DescrizioneProdotto AS Descrizione','P.Conversione12 AS Conversione')
                ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
                ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
                ->where('PRD.Confermato',1)
                ->where('PRD.Significativo',1)
                ->where('PRD.IdSchedaPrdOrdineAcc',0)
                ->where('DM.NRigaOrd',$ol)->orWhere('DM.NRigaOrd','0000'.$ol)
                ->first();
        }			

        return response()->json($result);
    }
	
	  public function produzioneBobine(Request $request)
    {
        $ordine = $request->ordineBy;

        $listBob = [];

        $results = DB::connection('sqlsrv_root_gp')->table('MQ_Produzione_24')
            //->select(DB::raw('COUNT(*) as numero_bobine'), DB::raw('SUM(cicli) as km'),'Ordine','Prodotto')
            ->select( 'cicli','Ordine','Prodotto','Dettaglio')
            ->where('cicli','>',0)
            ->where('cdMateriale',20)
            ->where('Prodotto','NOT LIKE','COL%')
            ->where('Anno',$request->anno)
            ->where('Mese',5)
            ->Where(function ($query) use ($ordine) {
                if ($ordine)
                    $query->Where('Ordine',$ordine);
            })
           // ->groupBy('Ordine','Prodotto')
            ->get();

        foreach ($results as $result){
            $ordine = str_replace(" ","",$result->Ordine);
            $stage = $this->getStage($result->Prodotto);


           // foreach ($objs as $obj){
                $dettaglio = explode("/", $result->Dettaglio);
                if(empty($listBob[$ordine])){
                    $listBob[$ordine]['fase'] = $dettaglio[count($dettaglio)-1];
                    $listBob[$ordine]['stage'] = $stage;
                    $listBob[$ordine]['gp'] = 1;
                    $listBob[$ordine]['km'] = round($result->cicli,2);
                    $listBob[$ordine]['materiale'] = $result->Prodotto;
                }elseif(!empty($listBob[$ordine]) && $listBob[$ordine]['fase'] == $dettaglio[count($dettaglio)-1]){
                    $listBob[$ordine]['gp'] = $listBob[$ordine]['gp'] + 1;
                    $listBob[$ordine]['km'] = round($listBob[$ordine]['km'] + $result->cicli,2);
                }elseif(!empty($listBob[$ordine]) && $dettaglio[count($dettaglio)-1] > $listBob[$ordine]['fase'] ){
                    $listBob[$ordine]['fase'] = $dettaglio[count($dettaglio)-1];
                    $listBob[$ordine]['gp'] =  1;
                    $listBob[$ordine]['km'] = round($result->cicli,2);
                }
           // }
        }

        $sheet_num_bobs = Sheets::spreadsheet('1EMdKONvY8Z1ghQnnhcM3hTE4jpGOQX81rooTQzRPSFM')->sheet('TOT BOB X OL')->all();
        foreach ($sheet_num_bobs as $row) {

            if (!empty($row[6]) && is_numeric($row[6])){
                $ordine = str_replace(" ","",$row[4]);
                if(empty( $listBob[$ordine]['stage']))
                    $listBob[$ordine]['stage'] = '-';
                $listBob[$ordine]['sheet'] = (!empty($listBob[$ordine]['sheet']) ? $listBob[$ordine]['sheet']:0) + $row[6];
            }

            if (!empty($row[10]) && is_numeric($row[10])){
                $ordine = str_replace(" ","",$row[8]);
                if(empty( $listBob[$ordine]['stage']))
                    $listBob[$ordine]['stage'] = '-';
                $listBob[$ordine]['sheet'] = (!empty( $listBob[$ordine]['sheet']) ?  $listBob[$ordine]['sheet']:0) + $row[10];
            }

            if (!empty($row[14]) && is_numeric($row[14])){
                $ordine = str_replace(" ","",$row[12]);
                if(empty( $listBob[$ordine]['stage']))
                    $listBob[$ordine]['stage'] = '-';
                $listBob[$ordine]['sheet'] = (!empty( $listBob[$ordine]['sheet'] ) ?  $listBob[$ordine]['sheet'] :0) + $row[14];

            }

        }

		ksort($listBob);
        return response()->json($listBob);
    }

    private function getStage($prodotto)
    {
        $tmp[1] = substr($prodotto, 0, 1);
        $tmp[2] = substr($prodotto, 1, 1);
        $tmp[3] = substr($prodotto, 2, 1);
        switch ($tmp[1]) {
            case 'P':
               if ($tmp[2] == 'E' && $tmp[3] == 'B') {
                    return 'JAC';
                }
                elseif ($tmp[2] == 'E' && $tmp[3] == 'S') {
                    return 'JAC';
                }else {
                   return '-';
                }
                break;
            case 'B':
                if ($tmp[2] == 'U') {
                    return 'BUF';
                }  else {
                    return '-';
                }
                break;
            case 'S':
                if ($tmp[2] == 'Z') {
                    return 'STR';
                } elseif ($tmp[2] == 'F') {
                    return 'JAC';
                } else {
                    return '-';
                }
                break;
            case 'F':
               if ($tmp[2] == 'C' || (is_numeric($tmp[2]) && $tmp[2] == 8)) {
                   return 'JAC';
                }
                elseif ($tmp[2] == 'I' && $tmp[3] == 'L') {
                    return 'STR';
                }else {
                   return '-';
                }
                break;
            default:
                return '-';
        }
    }
	
	public function bi_produzione(Request $request)
    {
		$dataBy = $request->get('data');
        if(empty($dataBy))
            $dataBy = date('Y-m-d');
        $groups = $request->get('groups');
        $lavorazioneBy = $request->get('lavorazione');
        $materialeBy = $request->get('materiale');
        $tipologiaBy = $request->get('tipologia');
		$ordineBy = $request->get('ordine');

        $result = DB::connection('sqlsrv_root_gp')
            ->table('Produzione as PRD')
            //->select(DB::raw('SUM(CASE WHEN UM.UM = "km" THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita'))
            ->select(DB::raw("CONCAT( Year(PRD.DataOraInizio),'-',MONTH(PRD.DataOraInizio)) AS Periodo"),'ProdNuovi.cdMateriale','R.Modello AS Macchina','P.NomeProdotto AS Prodotto','P.DescrizioneProdotto','P.Conversione12 As NumeroFibre', 'UM.UM AS UM', DB::raw("SUM(CASE WHEN UM.UM = 'km' THEN PRD.#Cicli ELSE PRD.#Cicli/1000 END) as quantita"))
            //->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine', 'P.Conversione12 As NumeroFibre','UMP.UM AS UM','PRD.#Cicli as Quantita')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
            ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
            ->join('UM','UM.IDUM','=','DSO.IDUM')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->join('GP_NX_AGG.dbo.AGG_PRODOTTI_TMP AS ProdNuovi','ProdNuovi.cdProdotto','P.NomeProdotto')
            ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1",function($join){
                $join->on("DT1.cdOrdine","=",DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                    ->on("DSO.CodPrel","=","DT1.numFase");
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy){
                    $dataBy = explode(' to ',$dataBy);
                    if(count($dataBy) == 2)
                        $query->whereBetween('PRD.DataOraFine', [$dataBy[0].' 00:00:00:000',$dataBy[1].' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraFine', $dataBy);
                }
            })
            ->whereIn('DT1.ControlKey',['PP03','ZP03'])
            ->where('PRD.Confermato',1)
            ->where('PRD.Significativo',1)
            ->where('PRD.IdSchedaPrdOrdineAcc',0)
            ->where('PRD.#Cicli','>',0)
            ->Where(function ($query) use ($tipologiaBy) {
                if ($tipologiaBy)
                    $query->where('ProdNuovi.cdMateriale', $tipologiaBy);
            })
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->where('P.NomeProdotto', 'LIKE','%'.$materialeBy.'%');
            })
			->Where(function ($query) use ($ordineBy) {
                if ($ordineBy)
                    $query->where('PRD.NRigaOrd','LIKE','%'.$ordineBy.'%');
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy){
                    switch ($lavorazioneBy) {
                        case 'bu':
                            $query->where('P.NomeProdotto','LIKE','BUF%');
                            break;
                        case 'sz':
                            $query->where('P.NomeProdotto','LIKE','SZ%');
                            break;
                        case 'sf':
                            $query->where('P.NomeProdotto', 'NOT LIKE', 'SFSPB1C0001%');
                            $query->Where('P.NomeProdotto', 'LIKE', 'SF%')->orWhere('P.NomeProdotto', 'LIKE', 'FC%');
                            $query->where('DM.NRigaOrd', 'NOT LIKE', '94%')->Where('DM.NRigaOrd', 'NOT LIKE', '93%');
                            $query->where('R.Modello','NOT LIKE', 'BV%');
                            break;
                        case 'mk':
                            $query->Where('P.NomeProdotto','LIKE','FC%');
                            $query->where('DM.NRigaOrd','LIKE','94%');
                            break;
                        case 'f':
                            $query->Where('P.NomeProdotto','LIKE','F1%')
                                ->orWhere('P.NomeProdotto','LIKE','F2%')
                                ->orWhere('P.NomeProdotto','LIKE','F3%')
                                ->orWhere('P.NomeProdotto','LIKE','F4%')
                                ->orWhere('P.NomeProdotto','LIKE','F5%')
                                ->orWhere('P.NomeProdotto','LIKE','F6%')
                                ->orWhere('P.NomeProdotto','LIKE','F7%')
                                ->orWhere('P.NomeProdotto','LIKE','F9%');
                            break;
                        case 's':
                            $query->Where('P.NomeProdotto','LIKE','S1%')
                                ->orWhere('P.NomeProdotto','LIKE','S2%')
                                ->orWhere('P.NomeProdotto','LIKE','S3%')
                                ->orWhere('P.NomeProdotto','LIKE','S4%')
                                ->orWhere('P.NomeProdotto','LIKE','S5%')
                                ->orWhere('P.NomeProdotto','LIKE','S6%')
                                ->orWhere('P.NomeProdotto','LIKE','S7%')
                                ->orWhere('P.NomeProdotto','LIKE','S8%')
                                ->orWhere('P.NomeProdotto','LIKE','S9%');
                            break;
                    }
                }
            })
            //->where('Conversione12',24) //umero Fibre
            ->groupBy( 'P.NomeProdotto','P.Conversione12','UM.UM','R.Modello','ProdNuovi.cdMateriale','P.DescrizioneProdotto',DB::raw('Year(DataOraInizio)'),DB::raw('Month(DataOraInizio)'))
            ->get();
			
	

       
        return response()->json($result);
    }
	
	public function strisciate(Request $request)
    {
        $olBy = $request->get('ordine');
        $materialeBy = $request->get('materiale');
        $umBy = $request->get('um');
        $dataBy = $request->get('data');
        $fibreBy = $request->get('num_fibre');
        $noQuantitaBy = $request->get('no_quantita');
        if(empty($dataBy))
            $dataBy = date('Y-m-d');

        $orderBy = $request->get('orderBy');
        $sortByName = $request->get('sortBy');
        if(empty($sortByName)){
            $sortByName = 'NumeroOrdineAcquisto';
            $orderBy = 'desc';
        }



        $result = DB::connection('sqlsrv_root_gp')
            ->table('200134_MB.dbo.Produzione as PRD')
            ->select('PRD.DataOraInizio','PRD.DataOraFine', 'O.NumeroOrdineAcquisto','R.Modello','P.NomeProdotto','P.DescrizioneProdotto','P.Conversione12', 'UM.UM', 'PRD.#Cicli as quantita')
            ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
            ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
            ->join('Ordini as O','O.IDOrdine','DM.IDOrdine')
            ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
            ->join('UM','UM.IDUM','=','DSO.IDUM')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->where('PRD.Confermato',1)
            ->where('PRD.Significativo',1)
            ->where('PRD.IdSchedaPrdOrdineAcc',0)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->where('P.NomeProdotto', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($olBy) {
                if ($olBy)
                    $query->Where('O.NumeroOrdineAcquisto', 'LIKE', '%' . $olBy . '%');
            })
            ->Where(function ($query) use ($umBy) {
                if ($umBy)
                    $query->Where('UM.UM', $umBy );
            })
            ->Where(function ($query) use ($fibreBy) {
                if ($fibreBy)
                    $query->Where('P.Conversione12', $fibreBy );
            })
            ->Where(function ($query) use ($noQuantitaBy) {
                if ($noQuantitaBy == 'true')
                    $query->where('PRD.#Cicli','>',0);
            })

            ->Where(function ($query) use ($dataBy) {
                if ($dataBy){
                    $dataBy = explode(' to ',$dataBy);
                    if(count($dataBy) == 2)
                        $query->whereBetween('PRD.DataOraInizio', [$dataBy[0].' 00:00:00:000',$dataBy[1].' 23:59:59:990']);
                    else
                        $query->whereDate('PRD.DataOraInizio', $dataBy);
                }
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($result);
    }
	
	public function DatiMacchina(Request $request)
    {

        $macchina = $request->get('macchina');
        $tipologia = $request->get('tipologia');
        $stato = $request->get('stato');
        $objs = DB::connection('sqlsrv_root_gp')
            ->table('STL_Info_Ordine_V')
            //->whereBetween('DataMisurazione', [date('Y-m-d H:i:s', strtotime('-5 minutes')), date('Y-m-d H:i:s')])
            ->Where(function ($query) use ($macchina) {
                if ($macchina)
                    $query->Where('MacchinaId', $macchina);
                else
                    $query->whereBetween('DataMisurazione', [date('Y-m-d H:i:s', strtotime('-5 minutes')), date('Y-m-d H:i:s')]);
            })
            ->Where(function ($query) use ($stato) {
                if ($stato == 'Fermo')
                    $query->whereNotNull('DescrizioneFermo')->whereNull('AF_DataOraFine');
            })
            ->Where(function ($query) use ($tipologia) {
                if ($tipologia)
                    $query->whereIn('MacchinaId',$this->getMacchine($tipologia));
            })
            ->orderBy('DataMisurazione', 'asc')
            //->orderBy('Macchina','asc')
            ->get();

        $result = [];
        $macchineId = [];
        foreach ($objs as $obj) {
            $macchineId[$obj->MacchinaId] = $obj->MacchinaId;
            $caratteristica = str_replace(" ", "_", $obj->Caratteristica);
            $old = (!empty($result[$obj->Macchina]['DatiMacchina'][$caratteristica]) ? $result[$obj->Macchina]['DatiMacchina'][$caratteristica] : 0);
            $result[$obj->Macchina]['DatiMacchina'][$caratteristica] = round($obj->ValoreMisurato, 2);
            $result[$obj->Macchina]['Macchina'] = $obj->Macchina;
            $result[$obj->Macchina]['MacchinaId'] = $obj->MacchinaId;
            $result[$obj->Macchina]['DatiMacchina']['quatroPuntoZero'] = true;
            $result[$obj->Macchina]['Ordine']['Ol'] = $obj->Ordine;
            $result[$obj->Macchina]['Ordine']['Prodotto'] = $obj->Prodotto;
            $result[$obj->Macchina]['Ordine']['Operatore'] = $obj->Operatore;
            $result[$obj->Macchina]['Ordine']['Fermo'] = $obj->DescrizioneFermo;
            $result[$obj->Macchina]['DatiMacchina']['UltimoDatoRicevuto'] = date('d-m-Y H:i:s', strtotime($obj->DataMisurazione));

            if (empty($result[$obj->Macchina]['DatiMacchina']['TotaleFermo']))
                $result[$obj->Macchina]['DatiMacchina']['TotaleFermo'] = $this->fermiProdottiMacchina($obj->MacchinaId, true);


            if (($caratteristica == 'Metri_Prodotti' || $caratteristica == 'Velocità_Linea')) {
                if (!is_null($obj->DescrizioneFermo) && is_null($obj->AF_DataOraFine))
                    $result[$obj->Macchina]['DatiMacchina']['Stato'] = $obj->DescrizioneFermo;
                elseif ($caratteristica == 'Metri_Prodotti' && $old < $obj->ValoreMisurato)
                    $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Run';
                elseif ($caratteristica == 'Metri_Prodotti' && $old >= $obj->ValoreMisurato)
                    $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Stop';
                elseif ($obj->ValoreMisurato > 0)
                    $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Run';
                else {
                    $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Stop';
                }

                if (empty($result[$obj->Macchina]['DatiMacchina']['TotaliMetiri']))
                    $result[$obj->Macchina]['DatiMacchina']['TotaliMetiri'] = round($this->metriProdottiMacchina($obj->MacchinaId, true), 3);//$this->metriProdottiMacchina($obj->MacchinaId);

            }
        }


        $objs = DB::connection('sqlsrv_root_gp')
            ->table('STL_Info_Ordine_V2')
            ->Where(function ($query) use ($macchina,$macchineId) {
                if ($macchina)
                    $query->Where('MacchinaId', $macchina);
                else
                    $query->whereNotIn('MacchinaId', $macchineId);
            })
            ->Where(function ($query) use ($tipologia) {
                if ($tipologia)
                    $query->whereIn('MacchinaId',$this->getMacchine($tipologia));
            })
            ->Where(function ($query) use ($stato) {
                if ($stato == 'Run'){
                    $query->whereNull('AP_DataOraFine')->whereNotNull('DescrizioneFermo')->whereNotNull('FermiCaloEfficienzaSec')
                        ->orWhereNull('AP_DataOraFine')->whereNull('DescrizioneFermo')->whereNull('FermiCaloEfficienzaSec');
                }
                else if ($stato == 'Fermo')
                    $query->whereNotNull('DescrizioneFermo')->whereNull('FermiCaloEfficienzaSec');

            })
            ->Where(function ($query) use ($tipologia, $macchina) {
                if (!$tipologia || !$macchina)
                    $query->where('AP_DataOraInizio', '>=', date('Y-m-d H:i:s', strtotime('-4 day')));
                else
                    $query->whereNull('AP_DataOraFine');
            })
            //->where('AP_DataOraInizio', '>=', date('Y-m-d H:i:s', strtotime('-2 day')))
            //->whereNull('AP_DataOraFine')
            ->orderBy('AP_DataOraInizio', 'asc')
            //->orderBy('Macchina','asc')
            ->get();

        foreach ($objs as $obj) {
            $result[$obj->Macchina]['DatiMacchina']['Velocità_Linea'] = null;
            $result[$obj->Macchina]['DatiMacchina']['Metri_Prodotti'] = $obj->AP_CicliAD;
            $result[$obj->Macchina]['Macchina'] = $obj->Macchina;
            $result[$obj->Macchina]['MacchinaId'] = $obj->MacchinaId;
            $result[$obj->Macchina]['DatiMacchina']['quatroPuntoZero'] = false;
            $result[$obj->Macchina]['Ordine']['Ol'] = $obj->Ordine;
            $result[$obj->Macchina]['Ordine']['Prodotto'] = $obj->Prodotto;
            $result[$obj->Macchina]['Ordine']['Fermo'] = $obj->DescrizioneFermo;
            $result[$obj->Macchina]['DatiMacchina']['UltimoDatoRicevuto'] = date('d-m-Y H:i:s', strtotime($obj->AP_DataOraInizio));

            if (empty($result[$obj->Macchina]['DatiMacchina']['TotaleFermo']))
                $result[$obj->Macchina]['DatiMacchina']['TotaleFermo'] = $this->fermiProdottiMacchina($obj->MacchinaId);

            if (is_null($obj->AP_DataOraFine) && !is_null($obj->DescrizioneFermo) && is_null($obj->FermiCaloEfficienzaSec))
                $result[$obj->Macchina]['DatiMacchina']['Stato'] = $obj->DescrizioneFermo;
            elseif (is_null($obj->AP_DataOraFine))
                $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Run';
            elseif (!is_null($obj->AP_DataOraFine))
                $result[$obj->Macchina]['DatiMacchina']['Stato'] = 'Stop';

            if (empty($result[$obj->Macchina]['DatiMacchina']['TotaliMetiri']))
                $result[$obj->Macchina]['DatiMacchina']['TotaliMetiri'] = ($obj->UMID != 8 ? round($this->metriProdottiMacchina($obj->MacchinaId, true), 3) : round($this->metriProdottiMacchina($obj->MacchinaId, true) / 1000, 3));

        }
        
        if($stato)
            $result = $this->checkStato($result, $stato);

        // Recupera velocita_minima dalla tabella machineries
        $macchineData = [];
        if (!empty($macchineId)) {
            try {
                $macchineData = DB::table('machineries')
                    ->whereIn('id_gp', array_keys($macchineId))
                    ->pluck('velocita_minima', 'id_gp')
                    ->toArray();
            } catch (\Exception $e) {
                // In caso di errore, usa array vuoto
                $macchineData = [];
            }
        }

        // Aggiunge velocita_minima ai risultati con controlli robusti
        foreach ($result as $macchina => $data) {
            if (!isset($data['DatiMacchina'])) {
                $result[$macchina]['DatiMacchina'] = [];
            }
            if (!isset($data['MacchinaId'])) {
                $result[$macchina]['DatiMacchina']['velocita_minima'] = 600;
                continue;
            }
            $macchinaId = $data['MacchinaId'];
            Log::info('DEBUG velocita_minima', [
                'macchinaId' => $macchinaId,
                'raw_value' => $macchineData[$macchinaId] ?? 'NOT_FOUND',
                'is_numeric' => isset($macchineData[$macchinaId]) ? is_numeric($macchineData[$macchinaId]) : false,
            ]);
            if (isset($macchineData[$macchinaId]) && is_numeric($macchineData[$macchinaId])) {
                $result[$macchina]['DatiMacchina']['velocita_minima'] = $macchineData[$macchinaId];
            } else {
                $result[$macchina]['DatiMacchina']['velocita_minima'] = 0;
            }
        }

        ksort($result);
        return response()->json($result);
    }

    public function DettaglioMacchina(Request $request)
    {
        $macchina = $request->get('macchina');
        $quatro = $request->get('quatroPuntoZero');
        $periodoDilter = $request->get('periodo');
        $select = json_decode($request->get('select'));
        $periodo = [date('Y-m-d 00:00:00'), date('Y-m-d H:i:s')];
        $result = [];

        if (!empty($select->dataDa) && $select->dataDa != $select->dataA) {
            $da = date("Y-m-d H:i:s", substr($select->dataDa, 0, 10));
            $a = date("Y-m-d H:i:s", substr($select->dataA, 0, 10));
            $periodo = [$da, $a];
        } elseif ($periodoDilter) {
            $d = explode(" to ", $periodoDilter);
            if (count($d) > 1)
                $periodo = [$d[0] . ' 00:00:00', $d[1] . ' 23:59:59'];
            else
                $periodo = [$periodoDilter . ' 00:00:00', $periodoDilter . ' 23:59:59'];
        }

        if ($quatro == 'false'){
            $minutes = 15;
            if(!empty($select->dataDa) && $select->dataDa - $select->dataA == -259200000){
                $minutes = 30;
            }elseif(!empty($select->dataDa) && $select->dataDa - $select->dataA == -604800000){
                $minutes = 60;
            }
            elseif(!empty($select->dataDa) && $select->dataDa - $select->dataA == -1296000000){
                $minutes = 90;
            }
            elseif(!empty($select->dataDa))
                $minutes = 120;
            $objs = DB::connection('sqlsrv_root_gp')
                ->table('STL_Produzione_Macchina')
                ->where('IDRisorsa', $macchina)
                ->whereBetween('DataInizio', $periodo)
                ->orderBy('DataInizio','asc')
                ->get();

            foreach ($objs as $obj){
                //$dataOraEvento = date('Y-m-d H:i:s', strtotime($obj->DataInizio));
                $dataOraFineEvento = (!is_null($obj->DataFine) ? strtotime(date('Y-m-d H:i:s', strtotime($obj->DataFine))) : strtotime(date('Y-m-d H:i:s')));
                if($obj->AF_DataOraInizio){
                    $dataOraInizioFermo = strtotime($obj->AF_DataOraInizio);
                    if($obj->AF_DataOraFine)
                        $dataOraFineFermo = strtotime($obj->AF_DataOraFine);
                    else
                        $dataOraFineFermo = strtotime(date('Y-m-d H:i:s'));
                }

                for($i = strtotime($obj->DataInizio); $i <= $dataOraFineEvento; $i = strtotime("+".$minutes." minutes",$i) ){
                    $result['Metri'][] = [date('Y-m-d H:i:s', $i), round($obj->Metri, 2), $obj->Ordine];
                    if(!empty($dataOraInizioFermo) && ($i >= $dataOraInizioFermo &&  $i <= $dataOraFineFermo))
                        $result['Fermi'][] = [date('Y-m-d H:i:s', $i), 2000,$obj->DescrizioneFermo];
                    else
                        $result['Fermi'][] = [date('Y-m-d H:i:s', $i), null,''];
                    $result['Linea'][] = [date('Y-m-d H:i:s', $i), null,''];
                    $result['Estrusione'][] = [date('Y-m-d H:i:s', $i), null,''];
                }

                $result['Metri'][] = [date('Y-m-d H:i:s', strtotime("+15 minutes",$i)), null,''];
                $result['Fermi'][] = [date('Y-m-d H:i:s', strtotime("+15 minutes",$i)), null,''];
                $result['Linea'][] = [date('Y-m-d H:i:s', strtotime("+15 minutes",$i)), null,''];
                $result['Estrusione'][] = [date('Y-m-d H:i:s', strtotime("+15 minutes",$i)), null,''];
            }
        }
        elseif (!empty($select->dataDa)) {
            switch ($select->dataDa - $select->dataA) {
                case 0:
                    $objs = DB::connection('sqlsrv_root_gp')
                        ->table('GP_NX_IC.dbo.CLN_EventiCOP_T AS DM')
                        ->join('Acd_Produzione_T AS P', 'P.AP_ID', 'DM.NoteMisurazione')
                        ->leftJoin('Risorse AS R', 'DM.Risorsa', 'R.IDRisorsa')
                        ->leftJoin('ACD_Fermi_T AS F', 'P.AP_ID', 'F.AF_FK_AcdProduzione')
                        ->leftJoin('Causali_Fermo AS CF', 'F.AF_FK_CausaleFermo', 'CF.IDCausaleFermo')
                        ->select('Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->whereBetween('DataMisurazione', $periodo)
                        ->where('R.IDRisorsa', $macchina)
                        ->whereNotNull('Prodotto')
                        ->groupBy( 'Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->orderBy('DataMisurazione', 'desc')
                        ->orderBy('Caratteristica', 'asc')

                        ->get();
                    break;
                case -2682000000:
                case -1296000000:
                case -604800000:
                case -259200000:
                    $objs = DB::connection('sqlsrv_root_gp')
                        ->table('GP_NX_IC.dbo.CLN_EventiCOP_T AS DM')
                        ->join('Acd_Produzione_T AS P', 'P.AP_ID', 'DM.NoteMisurazione')
                        ->leftJoin('Risorse AS R', 'DM.Risorsa', 'R.IDRisorsa')
                        ->leftJoin('ACD_Fermi_T AS F', 'P.AP_ID', 'F.AF_FK_AcdProduzione')
                        ->leftJoin('Causali_Fermo AS CF', 'F.AF_FK_CausaleFermo', 'CF.IDCausaleFermo')
                        ->select('Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->whereBetween('DataMisurazione', $periodo)
                        ->where('R.IDRisorsa', $macchina)
                        ->whereNotNull('Prodotto')
                        ->groupBy(DB::raw('DATEDIFF(MINUTE, 1000, DataMisurazione) / 60'), 'Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->orderBy('DataMisurazione', 'desc')
                        ->orderBy('Caratteristica', 'asc')

                        ->get();
                    break;
                default:
                    $objs = DB::connection('sqlsrv_root_gp')
                        ->table('GP_NX_IC.dbo.CLN_EventiCOP_T AS DM')
                        ->join('Acd_Produzione_T AS P', 'P.AP_ID', 'DM.NoteMisurazione')
                        ->leftJoin('Risorse AS R', 'DM.Risorsa', 'R.IDRisorsa')
                        ->leftJoin('ACD_Fermi_T AS F', 'P.AP_ID', 'F.AF_FK_AcdProduzione')
                        ->leftJoin('Causali_Fermo AS CF', 'F.AF_FK_CausaleFermo', 'CF.IDCausaleFermo')
                        ->select('Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->whereBetween('DataMisurazione', $periodo)
                        ->where('R.IDRisorsa', $macchina)
                        ->whereNotNull('Prodotto')
                        ->groupBy(DB::raw('DATEDIFF(MINUTE, 5000, DataMisurazione) / 60'), 'Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                        ->orderBy('DataMisurazione', 'desc')
                        ->orderBy('Caratteristica', 'asc')

                        ->get();

            }
        } else {
            $objs = DB::connection('sqlsrv_root_gp')
                ->table('GP_NX_IC.dbo.CLN_EventiCOP_T AS DM')
                ->join('Acd_Produzione_T AS P', 'P.AP_ID', 'DM.NoteMisurazione')
                ->leftJoin('Risorse AS R', 'DM.Risorsa', 'R.IDRisorsa')
                ->leftJoin('ACD_Fermi_T AS F', 'P.AP_ID', 'F.AF_FK_AcdProduzione')
                ->leftJoin('Causali_Fermo AS CF', 'F.AF_FK_CausaleFermo', 'CF.IDCausaleFermo')
                ->select('Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                ->whereBetween('DataMisurazione', $periodo)
                ->where('R.IDRisorsa', $macchina)
                ->whereNotNull('Prodotto')
                ->groupBy( 'Caratteristica', 'DataMisurazione', 'ValoreMisurato', 'AF_DataOraInizio', 'AF_DataOraFine','DM.Ordine','CF.DescrizioneFermo')
                ->orderBy('DataMisurazione', 'desc')
                ->orderBy('Caratteristica', 'asc')
                ->get();
        }



        $nRecord = $objs->count();
        $arr = [];
        if ($quatro == 'true')
            foreach ($objs as $key => $obj) {
                $caratteristica = str_replace(" ", "_", $obj->Caratteristica);
                $dataOraEvento = date('Y-m-d H:i:s', strtotime($obj->DataMisurazione));
                $test = date('Y-m-d H:i', strtotime($obj->DataMisurazione));
                $t = strtotime($dataOraEvento);
                $c = explode(":", $dataOraEvento);
                $minB = substr($c[1], 1, 1);
                $minA = substr($c[1], 0, 1);
                //$h = explode(" ", $c[0]);

                if ($nRecord > 900 && ($minB != 0 && $minB != 5)) {
                    continue;
                } elseif ($nRecord > 3548 && ($minA != 0 && $minB != 0))
                    continue;
                // elseif($nRecord > 6548 && ($h[1] != 1 && $h[1] != 4 && $h[1] != 8 && $h[1] != 12 && $h[1] != 16 && $h[1] != 20))
                //continue;
                //if(empty($arr[$t]))
                switch ($caratteristica) {
                    case 'Metri_Prodotti':
                        //if(empty( $arr[$t])){
                            $ordine = '';
                            $arr[$t]= $t;
                            if(!empty($obj->Ordine))
                                $ordine = explode("/",$obj->Ordine)[0];
                            $result['Metri'][] = [$dataOraEvento, ($obj->ValoreMisurato > 0 ? round($obj->ValoreMisurato, 2) : null), $obj->Ordine];

                            if (!is_null($obj->AF_DataOraInizio) && !is_null($obj->AF_DataOraFine)) {
                                if (!is_null($obj->AF_DataOraFine) && strtotime($obj->AF_DataOraFine) <= strtotime($dataOraEvento))
                                    $result['Fermi'][] = [$dataOraEvento, null];
                                elseif (strtotime($obj->AF_DataOraInizio) <= strtotime($dataOraEvento))
                                    $result['Fermi'][] = [$dataOraEvento, round(2000, 0),'Pippo'];
                                else
                                    $result['Fermi'][] = [$dataOraEvento, 2000];
                            }
                            elseif (!is_null($obj->AF_DataOraInizio) && is_null($obj->AF_DataOraFine))
                                $result['Fermi'][] = [$dataOraEvento, round(2000, 0),'Pluto'];
                            else
                                $result['Fermi'][] = [$dataOraEvento, null];
                       // }
                        break;
                    case 'Velocità_Linea':
                        if(!empty($arr[$t]))
                            $result['Linea'][] = [$dataOraEvento, ($obj->ValoreMisurato > 0 ? round($obj->ValoreMisurato, 2) : null)];
                       // $arr['Linea'][] = $t;
                        break;
                    case 'Velocità_Estrusore':
                        if(!empty($arr[$t]))
                            $result['Estrusione'][] = [$dataOraEvento, ($obj->ValoreMisurato > 0 ? round($obj->ValoreMisurato, 2) : null)];
                        //$arr['Estrusore'][] = $t;
                        break;
                    case 'Diametro':
                        if(empty($arr[$t]))
                            $result['Diametri'][] = [$dataOraEvento, ($obj->ValoreMisurato > 0 ? round($obj->ValoreMisurato, 2) : null)];
                        //$arr['Diametri'][] = $t;
                        break;
                }
            }
        ksort($result);
        return response()->json($result);
    }

    private function metriProdottiMacchina($macchina, $filter = null)
    {
        if (is_null($filter))
            $objs = DB::connection('sqlsrv_root_gp')
                ->table('STL_Info_Ordine_V')
                ->whereBetween('DataMisurazione', [date('Y-m-d H:i:s', strtotime('-5 minutes')), date('Y-m-d H:i:s')])
                ->where('MacchinaId', $macchina)
                ->whereNotNull('Prodotto')
                ->where('Caratteristica', 'Metri Prodotti')
                //->take(121)
                ->get()
                ->sum("ValoreMisurato");
        else
            $objs = DB::connection('sqlsrv_root_gp')
                ->table('Acd_Produzione_T')
                ->select('AP_CicliAD as ValoreMisurato')
                ->whereDate('AP_DataOraInizio', date('Y-m-d '))
                ->where('AP_FK_IDRisorsa', $macchina)
                //->take(121)
                ->get()
                ->sum("ValoreMisurato");

        return $objs;
    }

    private function fermiProdottiMacchina($macchina)
    {

        $minA = $minB = 0;
        $objs1 = DB::connection('sqlsrv_root_gp')
            ->table('ACD_Fermi_T AS F')
            ->leftJoin('Acd_Produzione_T AS P','F.AF_FK_AcdProduzione','P.AP_ID')
            ->select(DB::raw("DATEDIFF(minute, AF_DataOraInizio, AF_DataOraFine) AS Fermi"))
            ->whereBetween('F.AF_DataOraInizio', [date('Y-m-d 00:00:00:000'), date('Y-m-d H:i:s')])
            ->where('P.AP_FK_IDRisorsa', $macchina)
            ->whereNotNull('AF_DataOraInizio')
            ->whereNotNull('AF_DataOraFine')
            ->first();

        $objs2 = DB::connection('sqlsrv_root_gp')
            ->table('ACD_Fermi_T AS F')
            ->leftJoin('Acd_Produzione_T AS P','F.AF_FK_AcdProduzione','P.AP_ID')
            ->select(DB::raw("DATEDIFF(minute, AF_DataOraInizio, CURRENT_TIMESTAMP) AS Fermi"))
            ->whereBetween('F.AF_DataOraInizio', [date('Y-m-d 00:00:00:000'), date('Y-m-d H:i:s')])
            ->where('P.AP_FK_IDRisorsa', $macchina)
            ->whereNotNull('AF_DataOraInizio')
            ->whereNull('AF_DataOraFine')
            ->first();


        if(!empty($objs1->Fermi))
            $minA = $objs1->Fermi;
        if(!empty($objs2->Fermi))
            $minB = $objs2->Fermi;

        return date('G:i', mktime(0, $minA + $minB));
    }

    public function getMacchine($categoria)
    {
        $objs =  DB::table('machineries')->select('name_gp')->where('lavorazione',$categoria)->get();
        $res = [];
        foreach ($objs as $obj)
            $res[] = $obj->name_gp;

        return $res;
    }

    public function checkStato ($result, $stato)
    {
        foreach ($result as $macchina => $row){
            if($stato == 'Fermo'){
                if($row['DatiMacchina']['Stato'] == 'Run' || $row['DatiMacchina']['Stato'] == 'Stop')
                    unset($result[$macchina]);
            }
            elseif($row['DatiMacchina']['Stato'] != $stato)
                unset($result[$macchina]);
        }

        return $result;
    }
	
	public function exportBi(Request $request)
    {
        $name_file = date('dmY').'.xlsx';

        $export = new ProduzioneBi($request->tipologia,$request->gruppo, $request->data, $request->macchina,$request->materiale);
        
        return Excel::download($export, $name_file);
    }
	
	public function exportProduzione(Request $request)
    {
        $name_file = date('dmY').'.xlsx';

        $export = new Produzione($request->ol,$request->materiale, $request->data, $request->conversione,$request->um);

        return Excel::download($export, $name_file);

    }

    public function fabbisogni(Request $request)
    {
        $query = DB::connection('sqlsrv_gp')
            ->table('AGG_EXP_PRODUZIONE_FABB_TMP');

        $ordine = $request->get('ordine');
        $materiale = $request->get('materiale');
        $um = $request->get('um');
        $numFibre = $request->get('num_fibre');
        $data = $request->get('data');
        $noQuantita = $request->get('no_quantita');
        $idProduzione = $request->get('id_produzione');
        $sortBy = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $itemsPerPage = $request->get('itemsPerPage', 10);

        if ($idProduzione)
            $query->where('IDProduzione', $idProduzione);
        if ($ordine)
            $query->where('Ordine', 'LIKE', '%' . $ordine . '%');
        if ($materiale)
            $query->where('cdProdotto', 'LIKE', '%' . $materiale . '%');
        if ($um)
            $query->where('cdUM', $um);
        if ($numFibre)
            $query->where('NumeroFibre', $numFibre);
        if ($data) {
            $dataArr = explode(' to ', $data);
            if (count($dataArr) == 2)
                $query->whereBetween('DataEsportazione', [$dataArr[0] . ' 00:00:00', $dataArr[1] . ' 23:59:59']);
            else
                $query->whereDate('DataEsportazione', $data);
        }
        //if ($noQuantita == 'true')
          //  $query->where('Qta', '>', 0);

        if ($sortBy && $orderBy)
            $query->orderBy($sortBy, $orderBy);
        else
            $query->orderBy('DataEsportazione', 'desc');

        $results = $query->paginate($itemsPerPage);
        $results->getCollection()->transform(function ($item) {
            return array_map(function ($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'Windows-1252') : $value;
            }, (array) $item);
        });

        return response()->json($results);
    }

    public function produzione(Request $request)
    {
        $ordine = $request->get('ordine');
        $esportato = $request->get('esportato');
        $messaggio = $request->get('messaggio');
        $sortBy = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $itemsPerPage = $request->get('itemsPerPage', 10);


        $query = DB::connection('sqlsrv_gp')
            ->table('AGG_EXP_PRODUZIONE_TMP');

        if ($ordine)
            $query->where('Ordine', 'LIKE', $ordine . '%');
        if ($esportato !== null && $esportato !== '')
            $query->where('Esportato', $esportato);
        if ($messaggio)
            $query->where('MSG', 'LIKE', '%' . $messaggio . '%');

        if ($sortBy && $orderBy)
            $query->orderBy($sortBy, $orderBy);


        $results = $query->paginate($itemsPerPage);
        $results->getCollection()->transform(function ($item) {
            return array_map(function ($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'Windows-1252') : $value;
            }, (array) $item);
        });

        return response()->json($results);
    }

    public function prodotti(Request $request)
    {
        $query = DB::connection('sqlsrv_gp')
            ->table('AGG_PRODOTTI_TMP');

        $materiale = $request->get('materiale');
        $sortBy = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $itemsPerPage = $request->get('itemsPerPage', 10);

        if ($materiale)
            $query->where('cdProdotto', 'LIKE', '%' . $materiale . '%');

        if ($sortBy && $orderBy)
            $query->orderBy($sortBy, $orderBy);


        return response()->json($query->paginate($itemsPerPage));
    }

    public function ordini(Request $request)
    {
        $ordine = $request->get('ordine');
        $materiale = $request->get('materiale');
        $data = $request->get('data');
        $sortBy = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $itemsPerPage = $request->get('itemsPerPage', 10);

        $query = DB::connection('sqlsrv_gp')
            ->table('AGG_MASTER_TMP');

        if ($ordine)
            $query->where('cdOrdine', 'LIKE', '%' . $ordine . '%');
        if ($materiale)
            $query->where('cdProdotto', 'LIKE', $materiale . '%');
        if ($data)
            $query->where('dtOrdine', '>=', $data);

        if ($sortBy && $orderBy)
            $query->orderBy($sortBy, $orderBy);
        else
            $query->orderBy('cdOrdine', 'asc');

        $results = $query->paginate($itemsPerPage);
        $results->getCollection()->transform(function ($item) {
            return array_map(function ($value) {
                return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'Windows-1252') : $value;
            }, (array) $item);
        });

        return response()->json($results);
    }

    public function listaOrdini(Request $request)
    {
        $query = DB::connection('sqlsrv_gp')
            ->table('AGG_MASTER_TMP')
            ->select('cdOrdine')
            ->distinct();

        return response()->json($query->get());
    }
}
