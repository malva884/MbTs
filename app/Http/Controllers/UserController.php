<?php

namespace App\Http\Controllers;

use App\Console\Commands\SyncMateriali;
use App\Exports\CaviElaborazioneExport;
use App\Imports\PrMovementsImport;
use App\Jobs\FirmaCommesse;
use App\Jobs\ImportMagazzinoBi;
use App\Jobs\MaterialiSync;
use App\Jobs\TaskNotifiche;
use App\Models\FiTurnoverRow;
use App\Models\Gp;
use App\Models\HrEmployee;
use App\Models\HrHoursRequested;
use App\Models\HrHoursRequestedDetail;
use App\Models\PlWarehouse;
use App\Models\PlWarehouseInfo;
use App\Models\PrMaterial;
use App\Models\PrMovement;
use App\Models\PrStockCategorie;
use App\Models\PrWarehouseBi;
use App\Models\QtSupplier;
use App\Models\QtSupplierCertification;
use App\Models\QtSupplierNotice;
use App\Models\QtSupplierUser;
use App\Models\RegistroAccountWifi;
use App\Models\Task;
use App\Models\ToCableStructure;
use App\Models\ToCategory;
use App\Models\ToClient;
use App\Models\ToMaterial;
use App\Models\ToQuote;
use App\Models\ToQuoteCableStructure;
use App\Models\ToReel;
use App\Models\WfOrder;
use App\Print\TemplateZpl;
use App\Services\DocumentReaderService;
use App\Services\GeminiAiService;
use App\Services\GoogleSheet;
use App\Services\GoogleSlide;
use DateTime;
use Gemini\Enums\ModelType;
use Gemini\Laravel\Facades\Gemini;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_SpreadsheetProperties;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Koduarve\Ping\Ping as Ping;
use App\Exports\MensaExport;
use App\Imports\WorkStatusImport;
use App\Models\HrApproverRequest;
use App\Models\HrRequestPending;
use App\Models\LogActivity;
use App\Models\Permission;
use App\Models\PlAsset;
use App\Models\PlAssetMonitoring;
use App\Models\ToCable;
use App\Models\ToQuoteCable;
use App\Models\User;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use phpseclib3\Crypt\PublicKeyLoader;
use Revolution\Google\Sheets\Facades\Sheets;
use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Storage;


class UserController extends Controller
{


    public function getParsedWritableFile($gdrivefile)
    {
        $gdrivefile = new \Google_Service_Drive_DriveFile();//comment or delete, just here to check auto complete function names
        $parsedFile = new \Google_Service_Drive_DriveFile();

        // currently only allow these according to https://developers.google.com/drive/api/v3/reference/files#resource-representations
        $parsedFile->setName($gdrivefile->getName());//name
        $parsedFile->setMimeType($gdrivefile->getMimeType());//mimeType
        $parsedFile->setDescription($gdrivefile->getDescription());//description
        $parsedFile->setStarred($gdrivefile->getStarred());//starred
        $parsedFile->setTrashed($gdrivefile->getTrashed());//trashed
        $parsedFile->setParents($gdrivefile->getParents());//parents
        $parsedFile->setProperties($gdrivefile->getProperties());//properties  [object]
        $parsedFile->setAppProperties($gdrivefile->getAppProperties());//appProperties [object]
        $parsedFile->setCreatedTime($gdrivefile->getCreatedTime());//createdTime
        $parsedFile->setModifiedTime($gdrivefile->getModifiedTime());//modifiedTime
        $parsedFile->setWritersCanShare($gdrivefile->getWritersCanShare());//writersCanShare
        $parsedFile->setViewedByMeTime($gdrivefile->getViewedByMeTime());//viewedByMeTime
        $parsedFile->setFolderColorRgb($gdrivefile->getFolderColorRgb());//folderColorRgb
        $parsedFile->setOriginalFilename($gdrivefile->getOriginalFilename());//originalFilename
        $parsedFile->setCopyRequiresWriterPermission($gdrivefile->getCopyRequiresWriterPermission());//copyRequiresWriterPermission

        /*complex permissions*/
        /*
        contentHints.thumbnail.image
        contentHints.thumbnail.mimeType
        contentHints.indexableText
         */
        $contenthints = $gdrivefile->getContentHints();//could be null meaning further functions eg getThumbnail cause exception
        if ($contenthints) {
            $parsedFile->setContentHints($contenthints->getThumbnail()->getImage());
            $parsedFile->setContentHints($contenthints->getThumbnail()->getMimeType());
            $parsedFile->setContentHints($contenthints->getIndexableText());
        }

        /*no function to get indiviual attributes*/
        /*
contentRestrictions[].readOnly
ccontentRestrictions[].reason
         */
        $parsedFile->setContentRestrictions($gdrivefile->getContentRestrictions());
        return $parsedFile;
    }

    /**
     * @throws \Exception
     */
    public function index(Request $request)
    {
        /*   $rows = Sheets::spreadsheet('1t8I7GA7QMNFrnf0wmeJQzG0ttqeezaT41nm8Niu45jg')->sheet('Foglio1')->all();

           foreach ($rows as $key => $row){
               if($key > 0 && $row[0]){
                   $periodo = $row[4].'-'.$row[3].'-01';
                   $obj = DB::table('targets')
                       ->where('data_riferimento', $periodo)
                       ->where('tipo',$row[2])
                       ->where('titolo',$row[0])
                       ->first();
                   if(empty($obj->id)){
                       $obj = new Target();
                       $obj->titolo = $row[0];
                       $obj->data_riferimento = $periodo;
                       $obj->tipo = $row[2];
                       $terg = str_replace(",",'',$row[1]);
                       $obj->target = ($row[1] ? number_format($terg, 3, '.', ''):0.000);
                       $obj->user = 1;
                       $obj->valore = 0.000;
                       $obj->save();
                   }

               }
           }

           $colums = [
               'target_cc' => 'value_cc',
               'target_ofc' => 'value_ofc',
               'target_fkm' => 'fkm_ofc'
           ];
           $objs = DB::connection('mysql_old')->table('shipping_heads')->where('storege',1)->get();
           foreach ($objs as $obj){
               $data = explode("-",$obj->created_at);
               $periodo = $data[0].'-'.$data[1].'-01';
               foreach ($colums as $key => $colum){
                   $target = new Target();
                   $target->titolo = $colum;
                   $target->data_riferimento = $periodo;
                   $target->tipo = 2;
                   $target->target = $obj->$key;
                   $target->user = 3;
                   $target->valore = 0.000;
                   //$target->save();
               }
           }


           $result = DB::connection('sqlsrv_root_gp')->table('SAP_EXP_Production_T')
               ->select('SAP_EXP_Production_T.quantità','SAP_WorkingOrders.GMEIN as UM')
               ->join('SAP_WorkingOrders','SAP_WorkingOrders.AUFNR','SAP_EXP_Production_T.Ordine')
               ->whereYear('SAP_EXP_Production_T.DataMov','2024')
               ->whereMonth('SAP_EXP_Production_T.DataMov',05)
               ->where('SAP_EXP_Production_T.IDProduzione','777491')
               ->orderBy('SAP_EXP_Production_T.DataMov','desc')
               ->get('SAP_EXP_Production_T.IDProduzione');

           $result = DB::connection('sqlsrv_root_gp')->table('MQ_Produzione_24')
               ->select(DB::raw('SUM(cicli * Conversione) as quantita'))
               ->where('cdMateriale',20)
               ->where('Anno', 2024)
               ->where('Mese',4)
               //->skip(1)->take(2)
               ->first();


           Log::channel('stderr')->info($result->quantita);
   $sheet_rows= Sheets::spreadsheet('14JT0qf5yT5URuzxSgygmSBUDWengksRx0ndUOjPeuhQ')->sheet('Foglio1')->all();

           foreach ($sheet_rows as $key => $row){
               if($key > 1 && !empty($row[2]) && !empty($row[6])){
                   $dataApertura = explode(".",$row[2]);

                   $obj = new QtFai();
                   $obj->anno = $dataApertura[2];
                   $obj->num = explode("-",$row[1])[0];
                   $obj->data_creazione = $dataApertura[2].'-'.$dataApertura[1].'-'.$dataApertura[0];
                   if(!empty($row[3])){
                       $dataChiusura = explode(".",$row[3]);
                       $obj->data_chiusura =  $dataChiusura[2].'-'.$dataChiusura[1].'-'.$dataChiusura[0];
                   }
                   $obj->user = 3;
                   if(!empty($row[4]))
                       $obj->risultato = (explode(",",$row[4])[0] == 'Positivo' ? 1:2);
                   $obj->numero_fai = $obj->num.'-'.$obj->anno;
                   if(!empty($row[5]))
                       $obj->descrizione = $row[5];
                   if(!empty($row[6]))
                       $obj->ol = $row[6];
                   $obj->cod_cavo = '';
                   $obj->cod_materiale = (!empty($row[8]) ? $row[8]:'');
                   $obj->esito = '';
                   $obj->path_drive = (!empty($row[10]) ? $row[10]:'');
                   $obj->save();
               }

           }

         $materials = DB::connection('mysql_old')->table('material_stores')->get();
           Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
           //Sheets::addSheet('Check_Materiali');
           $tmp[]=['Material','Numero_Fibre_Portale','Numero_Fibre_Gp'];
           Sheets::sheet('Check_Materiali')->update($tmp);
           $i = 0;
           foreach ($materials as $material){
               $result = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')->where('cdProdotto',$material->material )->first();
               if(!empty($result->cdProdotto)){
                   if($material->fiber_count == null){
                       $cont = 1;
                   }
                   else{
                       $cont = $material->fiber_count;
                   }

                   if(round($result->Conversione,0) != $cont)
                       $tmp[] = [$result->cdProdotto,$cont,round($result->Conversione,0)];

               }
               $i++;
           }
           //Log::channel('stderr')->info($tmp);
           Sheets::sheet('Check_Materiali')->update($tmp);


           $data_riferimento = '2024-05-27';
           $corso_lavori = '1525089.21';
           $anno = '2024';
           $mese = '05';
           $magazzino = 52;
           $carteggaDriver = GoogleDrive::search(env('ID_GOOGLE_MAGAZZINI'), 'google', 'dir', $anno, false);
           $fileMagazzino = GoogleDrive::search($carteggaDriver, 'google', 'file', $mese, false);

           if(!empty($fileMagazzino)){
               $titolo = date('F Y', strtotime($anno.'-'.$mese.'-01'));
               $objs =  DB::connection('mysql_old')->table('warehouse_details')
                   ->select('warehouse_details.*','material_stores.fiber_count','material_stores.materialClass')
                   ->join('material_stores','material_stores.material','warehouse_details.material')
                   ->where('warehouse_details.warehouse_id',$magazzino)
                   ->get();

               $totale =  ['valore_ofc'=>0,'valore_cc'=>0,'fkm_ofc'=>0,'ckm_ofc'=>0,'ckm_cc'=>0];

               $head = New PrWarehouseHead();
               $head->user = 1;
               $head->titolo = $titolo;
               $head->anno = $anno;
               $head->mese = $mese;
               $head->data_riferimento = $data_riferimento;
               $head->totale = 0;
               $head->fkm_ofc = 0;
               $head->ckm_ofc = 0;
               $head->ckm_cc = 0;
               $head->corso_lavori = $corso_lavori;
               $head->path_drive = $fileMagazzino;
               $head->save();
               $sheet[] = ['Material','Description','Total Stock','Unit','Fiber Count','Fkm','Unit cost','Total','Last movement','Class'];
               $material_class = [];
               foreach ($objs as $obj){
                   $warehouse = New PrWarehouseRows();
                   $warehouse->warehouse_id = $head->id;
                   $warehouse->materiale = $obj->material;
                   $warehouse->descrizione = $obj->description;
                   $warehouse->quantita = $obj->total_stock;
                   $warehouse->fibre = $obj->fiber_count;
                   $warehouse->um = $obj->unit;
                   $warehouse->valore_unitario = $obj->unitary_value;
                   $warehouse->valore_totale = $obj->total_value;
                   $warehouse->crcy = $obj->crcy;
                   $warehouse->ultimo_movimento = $obj->last_gds_mvmt;
                   $warehouse->classe = $obj->materialClass;
                   $warehouse->save();

                   if(!empty($material_class[ $obj->materialClass]))
                       $material_class[ $obj->materialClass]['valore'] = $material_class[ $obj->materialClass]['valore'] + $obj->total_value;
                   else
                       $material_class[ $obj->materialClass]['valore'] = $obj->total_value;


                   $fkm = round(($obj->total_stock *  $obj->fiber_count),3);
                   $lastDate = date_create($obj->last_gds_mvmt);

                   $sheet[] = [$obj->material, $obj->description, $obj->total_stock, $obj->crcy, intval($obj->fiber_count), (is_null($fkm) ? '' : $fkm), $obj->unitary_value, $obj->total_value, date_format($lastDate, "m/d/Y"), $obj->materialClass];


                   if(!empty($obj->fiber_count)){
                       $totale['fkm_ofc'] = $totale['fkm_ofc'] + $fkm;
                       $totale['ckm_ofc'] =  $totale['ckm_ofc'] + $obj->total_stock;
                       $totale['valore_ofc'] =  $totale['valore_ofc'] + $obj->total_value;
                   }
                   else{
                       $totale['ckm_cc'] =  $totale['ckm_cc'] + $obj->total_stock;
                       $totale['valore_cc'] =  $totale['valore_cc'] + $obj->total_value;
                   }


               }

               $t = new TargetController();
               $targets = [
                   ['titolo' => 'value_cc', 'target' => 0, 'id' => $head->id],
                   ['titolo' => 'value_ofc', 'target' => 0, 'id' => $head->id],
                   ['titolo' => 'fkm_ofc', 'target' => 0, 'id' =>$head->id],
                   ['titolo' => 'ckm_cc', 'target' => 0, 'id' => $head->id],
                   ['titolo' => 'ckm_ofc', 'target' => 0, 'id' => $head->id],
               ];

               $t->store($targets, 4, $data_riferimento);

               $targets = [
                   'value_cc' => round($totale['valore_cc'], 2),
                   'value_ofc' => round($totale['valore_ofc'], 2),
                   'fkm_ofc' => round($totale['fkm_ofc'], 3),
                   'ckm_cc' => round($totale['ckm_cc'], 3),
                   'ckm_ofc' => round($totale['ckm_ofc'], 3),
               ];

               $t->update($targets, 4, $data_riferimento);


               $head->totale = round($totale['valore_cc'] + $totale['valore_ofc'], 2);
               $head->fkm_ofc = round($totale['fkm_ofc'], 3);
               $head->ckm_ofc = round($totale['ckm_ofc'], 3);
               $head->ckm_cc = round($totale['ckm_cc'], 3);
               $head->save();

               //Sheets::spreadsheet($fileMagazzino);

               $titolo = Date('Y-m-d H:i');
               //Sheets::addSheet('Details '.$titolo);
               //Sheets::addSheet('Summary '.$titolo);
               //Sheets::sheet('Details '.$titolo)->update($sheet);

               $arr = [];
               foreach ($material_class as $class => $values) {
                   $arr[] = [$class, (float)$values['valore']];
               }
               //Sheets::sheet('Summary '.$titolo)->update($arr);
           }

    */
        //Log::channel('stderr')->info('fatto');

        //$idSheet = GoogleSheet::createSheet('Prova File Condivisione','0AARdHHHpnqtAUk9PVA');

        //$drives = GoogleDrive::shared('0AARdHHHpnqtAUk9PVA','andrea.alampi@stl.tech','commenter');

        /*

                $obj = new SyFolderShared();
                $obj->titolo = 'Prova Cartella';
                $obj->path = '1111111';
                $obj->save();

                Log::channel('stderr')->info(number_format(237.58, 2, '.', ''));
                dd();
                $service = Storage::disk('google')->getAdapter()->getService();
                $parameters = [
                    "supportsAllDrives" => true,
                    "includeItemsFromAllDrives" => true,
                    "q" => "mimeType = 'application/vnd.google-apps.folder' and trashed=false",
                ];

                //$tmp = GoogleDrive::search('0AARdHHHpnqtAUk9PVA','google','dir','Gregorio',false);

                $tmps = collect($service->files->listFiles($parameters))->whereNotNull('name')->groupBy('driveId');

                Log::channel('stderr')->info(count($tmps));
                foreach ($tmps as $tmp)
                    Log::channel('stderr')->info((array)$tmp);


                $result = DB::Connection('sqlsrv_root_gp')
                    ->table('Produzione as PRD')
                    ->select('PRD.IDDettOrd')
                    ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                    ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
                    ->join('sqlsrv_gp.AGG_DETTAGLI_TMP as DT1','DT1.numFase','=','DSO.CodPrel')
                    ->paginate(100);


                $result = DB::connection('sqlsrv_root_gp')
                    ->table('Produzione as PRD')
                    //->select(DB::raw('SUM(CASE WHEN UMP.UM = "km" THEN PRD.#Cicli END) as quantita_OFC'))
                    //->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine','P.Conversione12 As NumeroFibre', 'UMP.UM AS UM', DB::raw('SUM(PRD.#Cicli) as quantita'))
                    ->select('P.NomeProdotto AS Prodotto', 'DM.NRigaOrd AS Ordine', 'P.Conversione12 As NumeroFibre','UMP.UM AS UM','PRD.#Cicli as Quantita')
                    ->join('Dettagli_sugli_ordini as DSO', 'DSO.IDDettagliOrdini', '=', 'PRD.IDDettOrd')
                    ->join('Dettagli_Master as DM','DM.idMaster','=','DSO.IDMaster')
                    ->join('Prodotti as P','P.IDProdotto','=','PRD.IDArticolo')
                    ->join('UM as UMP','UMP.IDUM','=','DSO.IDUM')
                    //->join('GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1', 'DT1.cdOrdine','=','DM.NRigaOrd')
                    ->join("GP_NX_AGG.dbo.AGG_DETTAGLI_TMP as DT1",function($join){
                        $join->on("DT1.cdOrdine","=",DB::raw("replace(DM.NRigaOrd, '00009', '9')"))
                            ->on("DSO.CodPrel","=","DT1.numFase");
                    })
                    ->whereBetween('PRD.DataOraInizio',['2024-01-01 00:00:00:000','2024-06-30 23:59:59:999'])
                    ->whereIn('DT1.ControlKey',['PP03','ZP03'])
                    ->where('PRD.Confermato',1)
                    ->where('PRD.Significativo',1)
                    ->where('PRD.IdSchedaPrdOrdineAcc',0)
                    //->where('Conversione12',24) Numero Fibre
                    //->groupBy('DM.NRigaOrd', 'P.NomeProdotto','P.Conversione12','UMP.UM')
                ->get();



                $arr[] = ['Ordine','Prodotto','Classe','Qauntità','UM','Fkm','Ckm','Data'];
                foreach ($result as $ordine) {
                    $tmp[1] = substr($ordine->Prodotto, 0, 2);
                    $tmp[2] = substr($ordine, 0, 2);
                    $tmp[3] = substr($ordine->Prodotto, 0, 1);
                    $tmp[4] = substr($ordine->Prodotto, 1, 1);
                    $tmp[5] = substr($ordine->Prodotto, 0, 3);
                    if(($tmp[1] == 'BO' || $tmp[1] == 'CO' || $tmp[1] == 'DO' || $tmp[1] == 'SM' || $tmp[1] == 'MU' || $tmp[1] == 'NO') AND  $tmp[2] != 94){
                        $tmp = ['material' => $ordine->Prodotto, 'description' => '', 'total_stock' => $ordine->Quantita, 'bun' => '', 'unitary_value' => 0, 'total_value' => 0.00, 'last_gds_mvmt' => '2024-01-01'];
                        $row = PrWarehouseRows::processing($tmp);
                    }elseif(( $tmp[3] == 'F' AND $tmp[4] != 8) || ($tmp[5] == 'FIL')){
                        $tmp = ['material' => $ordine->Prodotto, 'description' => '', 'total_stock' => $ordine->Quantita, 'bun' => '', 'unitary_value' => 0, 'total_value' => 0.00, 'last_gds_mvmt' => '2024-01-01'];
                        $row = PrWarehouseRows::processing($tmp);
                    }

                    // $arr[] = [$ordine->Ordine, $ordine->Prodotto, $row['class'], round($ordine->Quantita, 3), $ordine->UM, $row['material_class']['fkm'],  round($row['material_class']['ckm'], 3), $ordine->Data];
                    $arr[] = [$ordine->Ordine, $ordine->Prodotto, $row['class'], round($ordine->Quantita, 3), $ordine->UM, round($row['material_class']['fkm'], 0), round($row['material_class']['ckm'], 3),''];
                }
                    //Log::channel('stderr')->info($result->quantita);


                Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
                Sheets::sheet('Check_Produzione_2')->update($arr);


                $result = DB::connection('sqlsrv_gp')->table('AGG_PRODOTTI_TMP')
                    ->where('valore','>',0)
                    ->whereYear('dtUltimoMovimento','2024')
                    ->whereMonth('dtUltimoMovimento','09')
                    ->orderby('id', 'desc')
                    //->skip(1)->take(1)
                    ->count();

                Log::channel('stderr')->info($result);
         //Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
                //Sheets::addSheet('Materiali Fibre Null');
                $tmp[]=['Material','Numero_Fibre_Gp'];
                //foreach ($result as $materiale){
                    //$tmp[]= [$materiale->cdProdotto, $materiale->Conversione];
                //}
                Log::channel('stderr')->info($result);
                //Sheets::sheet('Materiali Fibre Null')->update($tmp);

        $preventivi = Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY')->sheet('preventivi testa')->all();
        $arr_prev = [];
        $tmp_c = [];
        foreach ($preventivi as $preventivo) {
            if (!empty($preventivo[0]) && !empty($preventivo[6])) {
                $cliente = DB::table('to_clients')->where('ragione_sociale', $preventivo[6])->first();
                if (!empty($cliente->id))
                    $preventivo[6] = $cliente->id;
                else {
                    $tmp_c[$preventivo[6]] = $preventivo[6];
                }

                $arr_prev[$preventivo[0]] = $preventivo;
            }
        }

                Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
                Sheets::addSheet('clienti2');
                $tmp[]=['Ragione sociale'];
                foreach ($tmp_c as $r){
                    $tmp[]= [$r];
                }
                Sheets::sheet('clienti2')->update($tmp);

        $dettagli = Sheets::spreadsheet('1DoOExt-jWmNqxE8ajSIME6yG4GdrO91buY6qyaEcie0')->sheet('preventiviAll')->all();
        $preventivi_creati = [];
        foreach ($dettagli as $dettaglo) {
            if (empty($dettaglo[0]))
                continue;

            if (empty($arr_prev[$dettaglo[0]])) {
                //Log::channel('stderr')->info('Preventivo non Trovato: '.$dettaglo[0]);
                continue;
            }


            if (!empty($preventivi_creati[$dettaglo[0]]))
                $preventivo_id = $preventivi_creati[$dettaglo[0]];
            else {
                $obj_preventivo = new ToQuote();
                $obj_preventivo->numero = $arr_prev[$dettaglo[0]][0];
                $obj_preventivo->data_preventivo = $arr_prev[$dettaglo[0]][1];      // TODO data non nel formato corretto
                $obj_preventivo->cu = $arr_prev[$dettaglo[0]][3];
                $obj_preventivo->parametro = $arr_prev[$dettaglo[0]][4];
                $obj_preventivo->cliente_id = $arr_prev[$dettaglo[0]][6];
                $obj_preventivo->rdo = $arr_prev[$dettaglo[0]][7];
                $obj_preventivo->data_rdo = $arr_prev[$dettaglo[0]][8];  // TODO data non nel formato corretto
                $obj_preventivo->nota = (!empty($arr_prev[$dettaglo[0]][10]) ? $arr_prev[$dettaglo[0]][10] : '');
                $obj_preventivo->save();
                $preventivo_id = $preventivi_creati[$obj_preventivo->numero] = $obj_preventivo->id;
            }

        }
-------  Da qui

        $cli_list = Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY')->sheet('clienti2')->all();
        $cliTpm = [];
        foreach ($cli_list as $c)
            $cliTpm[$c[0]] = $c[1];
        $preventivi = Sheets::spreadsheet('1wIkzfhJC7ofBxSqp2L1DzH7AFpZ5yV88k73VDBdzQHI')->sheet('Testata')->all();
        $arr_prev = [];
        $tmp_c = [];
        foreach ($preventivi as $preventivo) {
            if (!empty($preventivo[0]) && !empty($preventivo[6])) {
                $cliente = DB::table('to_clients')->where('ragione_sociale', $preventivo[6])->first();
                if (!empty($cliente->id))
                    $preventivo[6] = $cliente->id;
                elseif (!empty($cliTpm[$preventivo[6]])) {
                    $cliente = DB::table('to_clients')->where('ragione_sociale', $cliTpm[$preventivo[6]])->first();
                    if (!empty($cliente->id))
                        $preventivo[6] = $cliente->id;
                    else
                        $tmp_c[$preventivo[6]] = $preventivo[6];
                } else {
                    $tmp_c[$preventivo[6]] = $preventivo[6];
                }

                $arr_prev[$preventivo[0]] = $preventivo;
            }
        }

                        Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
                        $tmp[]=['Ragione sociale'];
                        foreach ($tmp_c as $r){
                            $tmp[]= [$r];
                        }
                        Sheets::sheet('clienti3')->update($tmp);



        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
        $sheets = ['Old']; // 'Quatro','Cinque','Sei'
        $preventivi_creati = [];
        foreach ($sheets as $sheet){
            $dettagli = Sheets::spreadsheet('1id16ouh-kOg3zyyhoWrB8Is59ZggNKrs6PjNJCRrkIs')->sheet($sheet)->all();
            foreach ($dettagli as $dettaglo) {
                if (empty($dettaglo[0]))
                    continue;

                if (empty($arr_prev[$dettaglo[0]]) || $arr_prev[$dettaglo[0]][6] == '9D07FE23-7B01-4A9A-949A-749D2ADEF5F7') {
                    //Log::channel('stderr')->info('Preventivo non Trovato: '.$dettaglo[0]);
                    continue;
                }

                if (!empty($preventivi_creati[$dettaglo[0]]))
                    $preventivo_id = $preventivi_creati[$dettaglo[0]]['id_preventivo'];
                else {
                    $data_preventivo = explode("/", $arr_prev[$dettaglo[0]][1]);
                    if(empty($arr_prev[$dettaglo[0]][8]))
                        $arr_prev[$dettaglo[0]][8] = $arr_prev[$dettaglo[0]][1];
                    $data_rdo = explode("/", $arr_prev[$dettaglo[0]][8]);
                    $obj_preventivo = new ToQuote();
                    $obj_preventivo->user = 5;
                    $obj_preventivo->numero = $arr_prev[$dettaglo[0]][0];
                    if(!empty($data_preventivo[2]))
                        $obj_preventivo->data_preventivo = $data_preventivo[2] . '-' . $data_preventivo[1] . '-' . $data_preventivo[0];      // TODO data non nel formato corretto
                    $obj_preventivo->cu = str_replace(",",".",$arr_prev[$dettaglo[0]][3]);
                    $obj_preventivo->parametro = $arr_prev[$dettaglo[0]][4];
                    $obj_preventivo->cliente_id = $arr_prev[$dettaglo[0]][6];
                    $obj_preventivo->rdo = (!empty($arr_prev[$dettaglo[0]][7]) ? $arr_prev[$dettaglo[0]][7]: '');
                    if(!empty($data_rdo[2]))
                        $obj_preventivo->data_rdo = $data_rdo[2] . '-' . $data_rdo[1] . '-' . $data_rdo[0];   // TODO data non nel formato corretto
                    $obj_preventivo->nota = (!empty($arr_prev[$dettaglo[0]][10]) ? $arr_prev[$dettaglo[0]][10] : '');
                    $obj_preventivo->save(); #TODO SAVE

                    $preventivo_id = $obj_preventivo->id;
                    $preventivi_creati[$obj_preventivo->numero]['id_preventivo'] = $obj_preventivo->id;


                }

                if (empty($preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]])) {
                    $cavo = new ToQuoteCable();
                    $cavo->preventivo_id = $preventivo_id;
                    $cavo->codice = $dettaglo[3];
                    $cavo->descrizione = $dettaglo[4];
                    $cavo->metri = str_replace(",",".",$dettaglo[2]);
                    $cavo->scarto = str_replace(",",".",$dettaglo[6]);
                    $cavo->pezzatura = str_replace(",",".",$dettaglo[5]);
                    $cavo->parametro = str_replace(",",".",$dettaglo[10]);
                    $cavo->posizione = $dettaglo[1];
                    $cavo->save(); #TODO SAVE

                    $preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]] = $cavo->id;
                    $preventivi_creati[$dettaglo[0]]['pezzatura_' . $dettaglo[3]] = $cavo->pezzatura;
                    $preventivi_creati[$dettaglo[0]]['posizione_dettaglio_'.$dettaglo[3]] = 1;
                }

                $p =  $preventivi_creati[$dettaglo[0]]['posizione_dettaglio_'.$dettaglo[3]]++;


                $costo = 0.00;
                $costo_m = 0.0000;
                if(!empty($dettaglo[17])){
                    $c = explode(",",$dettaglo[17]);
                    if(count($c) == 2)
                        $costo = $c[0].'.'.$c[1];
                    elseif(count($c) == 3)
                        $costo = $c[0].''.$c[1].'.'.$c[2];
                }

                if(!empty($dettaglo[18])){
                    $cm = explode(",",$dettaglo[18]);
                    if(count($cm) == 2)
                        $costo_m = $cm[0].'.'.$cm[1];
                    elseif(count($cm) == 3)
                        $costo_m = $cm[0].''.$cm[1].'.'.$cm[2];
                }

                $det_cav = new ToQuoteCableStructure();

                $det_cav->cavo_id = $preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]];
                $det_cav->centro = $dettaglo[12];
                $det_cav->materiale = $dettaglo[13];
                $det_cav->descrizione = $dettaglo[14];
                if(!empty($dettaglo[15]))
                    $det_cav->diametro = str_replace(",",".",$dettaglo[15]);
                if(!empty($dettaglo[16]))
                    $det_cav->peso =  str_replace(",",".",$dettaglo[16]);
                $det_cav->costo = $costo;
                $det_cav->costo_materia_prima = $costo_m;
                if(!empty($dettaglo[20]))
                    $det_cav->ordinata = str_replace(",",".",$dettaglo[20]);
                $det_cav->elementi = (!empty($dettaglo[21]) ? str_replace(",",".",$dettaglo[21]): null);
                $det_cav->ore_macchina = (!empty($dettaglo[24]) ? str_replace(",",".",$dettaglo[24]): 0);
                $det_cav->nota = (!empty($dettaglo[23]) ? $dettaglo[23]:'');
                $det_cav->costo_centro = (!empty($dettaglo[19]) ? str_replace(",",".",$dettaglo[19]) : 0);
                $det_cav->costo_lavorazione = (!empty($dettaglo[22]) ? str_replace(",",".",$dettaglo[22]): 0);
                $det_cav->posizione = $p;
                $det_cav->save(); #TODO SAVE

                if ($dettaglo[12] == 'COLL') {
                    $diametro = str_replace(",",".",$dettaglo[15]);
                    $pezzatura = str_replace(",",".",$dettaglo[5]);
                    if(!empty($diametro) && !empty($cavo->metri)) {
                        $capacita = ($diametro * $diametro) * $pezzatura;
                        $bobina = ToReel::get_bobina(round($capacita,0));

                        $cavo = ToQuoteCable::where('id', $det_cav->cavo_id)->first();
                        if(!empty($bobina->id)){
                            $cavo->bobina_id = $bobina->id;
                            $cavo->bobina = $bobina->bobina;
                            $cavo->bobina_numero = round($cavo->metri / $pezzatura,0);
                            $cavo->peso = $bobina->peso;
                            $cavo->m3 = $bobina->m3;
                            $cavo->m3_totale = round($request->m3 * $cavo->bobina_numero, 2);
                            $cavo->totale_costo_bobine = round($bobina->costo * $cavo->bobina_numero, 4);
                            $cavo->costo_bobina = $bobina->costo;
                        }
                        $cavo->save();
                        if(!empty($bobina->id))
                            $cavo->calcola_totali();
                    }
                }
            }
        }


        dd('ok');
*/

        /*
                stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
                $token = "exWm8aP5MjxLUj2b28$2Fd";
                $path = 'https://app.metallurgicabresciana.it//turni/mb/richieste/api/get.php?';
                $path .= 'tk=' . $token;
                $getMovieList = file_get_contents($path);
                $result = json_decode($getMovieList);

                if ($result->stato == 200) {
                    $richieste = [];
                    foreach ($result->list as $row) {
                        if (empty($richieste[$row->richiesta_id]['id'])) {
                            $dependente = DB::connection('mysql_old')->table('employees')
                                ->select('id', 'centro')
                                ->where('matricola', $row->matricola)
                                ->first();
                            if (!empty($dependente->id)) {
                                $richiesta = new HrHoursRequested();
                                $richiesta->bacheca_id = $row->richiesta_id;
                                $richiesta->data_richiesta = $row->data_richiesta;
                                $richiesta->bacheca_dipendente_id = $row->dipendente;
                                $richiesta->dipendente_matricola = $row->matricola;
                                $richiesta->dipendente_cognome = $row->cognome;
                                $richiesta->dipendente_nome = $row->nome;
                                $richiesta->tipologia = $row->tipologia;
                                $richiesta->centro_di_costo = $dependente->centro;
                                $richiesta->save();
                                $userNotifica = HrApproverRequest::select('users.email','users.full_name','users.id')
                                    ->join('users','users.id','hr_approver_requests.user_id')
                                    ->where('centro_ci_costo',$dependente->centro)
                                    ->orderBy('livello','desc')->first();

                                $richieste[$row->richiesta_id] = [
                                    'id' => $richiesta->id,
                                    'dipendente' => $row->cognome.' '.$row->nome,
                                    'user_notifica_email' => $userNotifica->email,
                                    'user_notifica_id' => $userNotifica->id,
                                    'user_notifica_nome' => $userNotifica->full_name,
                                ];

                            }

                        }

                        if (!empty($richieste[$row->richiesta_id]['id'])) {
                            $dettaglio = new HrHoursRequestedDetail();
                            $dettaglio->richiesta_id = $richieste[$row->richiesta_id]['id'];
                            $dettaglio->bacheca_id = $row->richiesta_id;
                            $dettaglio->bacheca_dipendente_id = $row->dipendente;
                            $dettaglio->dipendente_matricola = $row->matricola;
                            $dettaglio->data = $row->data;
                            $dettaglio->tipologia = $row->tipologia;
                            $dettaglio->save();
                        }
                    }

                    foreach ($richieste as $richiesta){
                        $approval = new HrRequestPending();
                        $approval->richiesta_id = $richiesta['id'];
                        $approval->user_id = $richiesta['user_notifica_id'];
                        $approval->save();
                    }
                }
        $buf = $str = $jac = 0;
                $tmp = [];
                $periodo = [date('Y-m-01 00:00:00'),date('Y-m-d H:i:s')];
                $buf_count = DB::table('qt_checker_reports')
                    ->select('ol','coil',DB::raw('COUNT(DISTINCT coil) as bob'))
                    ->where('stage', 'BUF')
                    ->whereBetween('date_create', $periodo)
                    ->groupBy(['ol','coil'])
                    ->get();

                stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);

                $nome = '';
                $azienda = '';
                $wifi_user = '';
                $wifi_pass = '';
                $scadenza = '';
                $code = '';
                $ip = '10.141.3.127';

                $zpl = '^XA~TA000~JSN^LT0^MNM^MTD^PON^PMN^LH0,0^JMA^PR3,3~SD10^JUS^LRN^CI0^XZ'
                    . '^XA'
                    . '^MMT'
                    . '^PW575'
                    . '^LL0406'
                    . '^LS0'
                    . '^FO0,0^GFA,29952,29952,00072,:Z64:'
                    . 'eJzt3TFOw0AQBdC1UmyHWwqUXIMiUq6VgsJH81F8BA4QYfDaQXSRmBEK0fvFjKsna7ffX4qIyAPkMEcyXpkaYuZ52JxT0HnfnO+PX57KZd3dJcSU8rGuOgWdw9BWPwSdOrZ1CjJlN7X1FnXKek/nsHP+oUVyXEY3hZ19c8aw87SMXdzpl1GHsNOIGmbKbnH6BGcs2yHF0mU6+wRn+hovcadwbjvHJOeVw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD4XA4HA6Hw+FwOBwOh8PhcDgcDofD+YfOc5Jzb++QP6qT9R7+vb3zn9VfUBOcoeT1O2T1TXRxp11VVh9HVj9IWl9JVn9KVp9LVr9MnYLO1neT1b9TgrVC8/U/svqJsvqSRERE/iqf6PARug==:3476'
                    . '^FO0,256^GFA,08704,08704,00068,:Z64:'
                    . 'eJztV09oHFUc/r03s/PG3XV3Cwkdt5PM2IAuMYRtvSx2rVt6EMRDeihegt3Ug0E8TOihK0ybt1lJQil67UFke5Eciix4CSjyhpRGRCSeVMhhxIM5CE6ghwiV+pt/2912VizrSebLzsvMe2++983v37wBSJEiRYoUKVKkSDEOVB784+NwZBqPWh+LoI+aeunhXvIANYO2f70FyyMoJBfOjtCx4LcXYx3MIj8lTyTXcFSwpCF1K1idR5eaq5gjOFxsXDVRx0V/vK9jRegimSNQoFtJQ/QkxEbxscNP8aRpADW/ybuJOiZRQuZcpINskN0RHIECljioUuxW4/igWbKeTBHOkBKHZIKOmSCRjrzORoRH6JCpRB0UTPB/IbRKXovPXWnwBgXgCI2SyJGBSSALsQ7L0lvxwiW9MTBPB+Mt321JHCr+4bNG/KJb9aKBPDQHF9WgRjiIRA4ZcyUDkQ7iiCU3GqgdG7qhCk0QUjIHZorZzxaJ8k48qznDB3WYqKIhQSLHJMDCRKyDMbIRzSLiKhmc10DHNAySyJH1TRLXj3yWFqN+wr+ng/NEHjn0ZA4Zf41Yh67LRtTPyL48xIE+b7SSOehg8ahW1LhEFKTqUI5yE3ksypM4MigbYh1Wq2RH/TqzSgPTCLqFON6IfImWCQX3zIOov5J3zSEODqQtcokc4UOHOojjLsUhZj3niEEOdK3EhAFJGDQ+oaLdjc7dy53BNQlmrKLw+USOzMUAgQ7GnCDESr6k3aHS6XPoBtgJDGiPzc1NPII1lVxH8U9cjNjOjSG3EHzmWpn3Rum4MBnpyBtKETkk1MKU4dLpc1iryWmL9fTkSTyCc21WzvldyFPQKvF8Hrfue8nm8ONjYiHyS9XWfcP74T5lXYkViKhlzp8P4ruGXzP41KoZLWYua3MQpBCc4XeiCVKox4UC6fWjPzfEIQ/kbcOzfMNP48UqiQMlShELbMlV4ruKQxx0oJq2e01860l/4F3LVAw/c0FyqegH01A6+tU0rmOEeA6uToLDz4zBhMn8Fb9tn8zdLLqBhvWUhmFK8JC6vlG04ak5TjHiwT+GgfUUVQQ6FOYwASUmZFxyGvxaDn4/6gxggBKEzuMUaI++RdTsGobpQYHPf1rgc+DVNZC+4C2pW/goGJ+FLCquKVAAU7oBogr18MYMTMTvF82QGWffGW3LxswgXssQhtH2jFJrMZhqGwbZI3en4Jokpi6xpX3yY+g8FSpYLAJ7VMtqDorqS5J79Ar0JNGbEQdZqVv7pRtujbarZbrFNl+Gzzb4UT1/WWWdbmSP/vu2WdcM0EtvM+GhiqLjve54OhMrv4kgdcihqMu2ob3Ddg3i2qXfdYOJyB5mXETEXHUaKuadHOkeUPcEFR9Tt1Lg3XUeLEd/hTnVrVc7yvos5dbzvcqcEroo40uYJI0S+qxl6bCy0zIkxzPEKnNwHcuQhEEcXyy7C/Yx79oPzJBtRrzzh2gwiOzhc3Fex71FT1Tga7lXp2vdMv8qtzad411cskzXAJXkVNiecb9cY2XYzhKx7nR72bDGyL7zMxca13F3cCgsJqZcm8nuVWLjcvjci0XAaz927RJGr3NfZjZp6Wyn2HHcehjJNLCFic5X4TaYCj8tkF68Qa15bXme8s0ybGVVggVs2aSusibU7AG1KsqHqE5UoviI9oU2WGQXqzk/71j1U84n8uH+irdY3KitEstABZhCTYyXDFrIevbwyNBfZc86h6Ff1IrfVvA15uJWrDkDnfXmN+fW8pqzLbq3yvnqt9ScM9FoEm+cgOwz/OispTnV2epy9kT7duSXaJ88kRFkH5YcxvRzPzty3to9xXdL10sreX1pX2D5uASoVV8hut1cuWe93/TyV5R3o3wxwygpfA7QgxeJSs3LZXJLFTdPw011q/mCYs5g1TqLhQzfX6qjaK7JN83NdlfdUd8MOSYDHZONDH4TLMAU8YjQdbLHAGPFYh8IPSN03KMcf9iA1/bAIA+MPYN4RlESGCNR6c/yR+2TuJ7c/RjkgfZJ3P9XHP+I5C3L0+E4H5/jzPgU5N74HFJ+fA69Nj5H1RyfoyHG52jzsSlI4sfs04EW/gOOkV/+T8Fhjs8Rf+SlSJEiRYoUKf4H+Bt99aMl:42E2'
                    . `^FT39,76^A0N,39,38^FH\^FD'.$nome.'^FS`
                    . `^FT136,109^A0N,20,19^FH\^FD'.$azienda.'^FS`
                    . '^FT39,156^A0N,20,19^FH\^FDWifi-Username:^FS'
                    . '^FT39,192^A0N,20,19^FH\^FDWifi-Password:^FS'
                    // eslint-disable-next-line camelcase
                    . `^FT189,156^A0N,20,19^FH\^FD'.( $wifi_user !== null ? $wifi_user : '-').'^FS`
                    // eslint-disable-next-line camelcase
                    . `^FT189,192^A0N,20,19^FH\^FD'.$wifi_pass !== null ? $wifi_pass : '-'.'^FS`
                    . '^FT39,240^A0N,20,19^FH\^FDValido fino al:^FS'
                    . `^FT189,240^A0N,20,19^FH\^FD'.$$scadenza.'^FS`
                    . '^FT361,281^BQN,2,6'
                    . `^FH\^FDLA,'.$code.'^FS`
                    . `^FT92,378^A0N,20,19^FH\^FD'.$code.'^FS`
                    . '^PQ1,0,1,Y^XZ';

                $fp=pfsockopen($ip,9100);
                fputs($fp,$zpl);
                fclose($fp);
        */
        //GoogleDrive::set_role('1smlLQ2leYo3YUbsdPPfu_5EqhVc2uGKs','antonio.guerreschi@stl.tech','commenter','delete');
        /*
                ini_set('max_execution_time', -1);

                $magazzino = DB::connection('sqlsrv_gp')->table('AGG_GIACENZE')
                    ->select('cdProdotto','cdLotto','Giacenza')
                    ->get();
                Log::channel('stderr')->info('Giacence  Scaricate');
                $giacenze = [];
                foreach ($magazzino as $row)
                    $giacenze[$row->cdProdotto][$row->cdLotto] = $row->Giacenza;

                Log::channel('stderr')->info('Giacence  Array');

                $origine = Sheets::spreadsheet('1L5T79fj7Ox-1Z5NRmJmblND-BQfv5pmhHgzW3nN1IJw')->sheet('Origine')->all();
                $tmp = [];
                Log::channel('stderr')->info('Inizio Check');
                foreach ($origine as $c){
                    $lotto = str_replace('@','',$c[2]);
                    if(empty($giacenze[$c[0]]))
                        $tmp[] = [$c[0],'-','Prodotto Mancante'];
                    else{
                        if(empty($giacenze[$c[0]][$lotto]))
                            $tmp[] = [$c[0],$lotto,'Lotto Mancante'];
                        //elseif($c[3] != $giacenze[$c[0]][$c[2]])
                           //$tmp[] = [$c[0],$c[2],'Giacenza Diversa',$c[3],$giacenze[$c[0]][$c[2]]];
                    }
                }
                Log::channel('stderr')->info('Fine  Check');
                Sheets::sheet('Risultato2')->update($tmp);

                $result = DB::connection('mysql_portale')->table('users')->get();
                */

        // $result = DB::connection('mysql_old')->table('report_production_scraps')->get();

        //$idPresentazione = '1V99muqBVU11RJOUtbC7MHCvAPiYX4TxV3Tub4vV6hhc';//GoogleSlide::create('Prova');
        //$idPagina = GoogleSlide::addPage($idPresentazione);
        //GoogleSlide::addImage($idPresentazione, $idPagina);

        //$result = DB::connection('mysql_portale')->table('users')->get();
        //Log::channel('stderr')->info((array)$result->models);
        /*
                $rows= Sheets::spreadsheet('1wFdZq_7V1PKOXsSb7EXNm29rWJELcqz57ySGZh8ccBk')->sheet('Sheet1')->all();

                $i = 0;
                $result = [];
                foreach ($rows as $row){

                    if($i > 7 && !empty($row[11])){

                        if($i == 4758)
                            break;
                        $tmp = explode(" ",$row[11]);

                        if(array_search('programmata',$tmp)){
                            $i++;
                            continue;
                        }

                        if(array_search('automatica',$tmp)){
                            $i++;
                            continue;
                        }

                        $result[] = [
                            (!empty($row[8]) ? $row[8] : ''),
                            (!empty($row[9]) ? $row[9] : ''),
                            (!empty($row[10]) ? $row[10] : ''),
                            (!empty($row[11]) ? $row[11] : ''),
                        ];
                    }

                    $i++;
                }
                Sheets::sheet('Result')->update($result);

                $rows = Sheets::spreadsheet('1R66mm9U0F9mXjqC2Bl0P_vdfQLjeecetG9VscER4n7U')->sheet('Inventario')->all();
                $i = 0;
                foreach ($rows as $row){
                    if($i > 0 && !empty($row[2])){
                        $obj = new PlWarehouse();
                        $obj->marca = $row[2];
                        $obj->descrizione = $row[4];
                        $obj->tipologia = $row[0];
                        $obj->pn_interno = $row[1];
                        $obj->pn_oem = $row[3];
                        $obj->quantita = $row[5];
                        $obj->quantita_minima = 1;
                        $obj->data_fornitura = date('Y-m-d');
                        $obj->save();

                        if(!empty($row[8])){
                            $info = new PlWarehouseInfo();
                            $info->magazzino_id = $obj->id;
                            $info->tipologia = $row[6];
                            $info->link = $row[8];
                            $info->prezzo = $row[9];
                            $info->sito = $row[7];
                            $info->save();
                        }
                    }
                    $i++;
                }


                $periodi = [
                    ['anno' => 2024, 'mese' => '04'],
                    ['anno' => 2024, 'mese' => '05'],
                    ['anno' => 2024, 'mese' => '06'],
                    ['anno' => 2024, 'mese' => '07'],
                    ['anno' => 2024, 'mese' => '09'],
                    ['anno' => 2024, 'mese' => '09'],
                    ['anno' => 2024, 'mese' => '10'],
                    ['anno' => 2024, 'mese' => '11'],
                    ['anno' => 2024, 'mese' => '12'],
                    ['anno' => 2025, 'mese' => '01'],
                    ['anno' => 2025, 'mese' => '02'],
                    ['anno' => 2025, 'mese' => '03'],
                ];

                $result = [];
                $sheet_bu = [];
                $sheet_sz = [];
                $sheet_sf = [];
                $h = [];
                $row = 1;
                foreach ($periodi as $periodo){
                    $weeks = DB::connection('mysql_old')->table('report_production_scraps')
                        ->select(DB::raw("CONCAT( Year(date_a),'-',MONTH(date_a)) AS Periodo"), 'report_production_scraps.*')
                        ->whereYear('date_da',$periodo['anno'])
                        ->whereMonth('date_da',$periodo['mese'])
                        ->orderBy('date_a', 'asc')
                        ->get();
                    $i = 1;
                    foreach ($weeks as $week){
                        $bu = Gp::totaleDatiProduzione('bu', [$week->date_da, $week->date_a]);
                        $sz = Gp::totaleDatiProduzione('sz', [$week->date_da, $week->date_a]);
                        $sf = Gp::totaleDatiProduzione('sf', [$week->date_da, $week->date_a]);
                        if(empty($sheet_bu[0])){
                            $sheet_bu[$row][0] = date('M-Y',strtotime($week->date_da));
                            $sheet_sz[$row][0] = date('M-Y',strtotime($week->date_da));
                            $sheet_sf[$row][0] = date('M-Y',strtotime($week->date_da));
                            $h[0] = '';
                        }
                        if(!empty($bu->Periodo)){
                            $sheet_bu[$row][$i] = round((1 - ($bu->Valore - $week->buffering) / $bu->Valore) * 100,1);
                            $sheet_sz[$row][$i] = round((1 - ($sz->Valore - $week->stranding) / $sz->Valore) * 100,1);
                            $sheet_sf[$row][$i] = round((1 - ($sf->Valore - $week->sheathing) / $sf->Valore) * 100,1);

                        }else{
                            $sheet_bu[$row][$i] = 0;
                            $sheet_sz[$row][$i] = 0;
                            $sheet_sf[$row][$i] = 0;
                        }
                        $h[$i] = 'week '.$i;
                        $i++;
                    }

                    ksort($sheet_bu[$row]);
                    ksort($sheet_sz[$row]);
                    ksort($sheet_sf[$row]);
                    $result['bu'][] = $sheet_bu[$row];
                    $result['sf'][] = $sheet_sf[$row];
                    $result['sz'][] = $sheet_sz[$row];
                    $row++;
                   // ksort($h);
                }



                Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');

                Sheets::sheet('bu')->update(  $result['bu']);
                Sheets::sheet('sz')->update(  $result['sz']);
                Sheets::sheet('sf')->update(  $result['sf']);

                dd();

                $files =  GoogleDrive::search('0AARdHHHpnqtAUk9PVA','google','files');
                foreach ($files as $file){
                    //Log::channel('stderr')->info((array)$file);
                    Log::channel('stderr')->info('Nome File: '.$file->name);
                    Log::channel('stderr')->info($file->createdTime);

                    dd();
                }
        $sql = "SELECT * FROM AGG_GIACENZE
            WHERE cdProdotto like 'SZ%'
            ORDER BY cdProdotto
              ";
                $result = DB::connection('sqlsrv_gp')->select($sql);


                $month = 5;
                $year = 2025;

                $week = date("W", strtotime($year . "-" . $month ."-01")); // weeknumber of first day of month

                Log::channel('stderr')->info(date("d/m/Y", strtotime($year . "-" . $month ."-01")) ." - ");

                $unix = strtotime($year."W".$week ."+1 week");
                $arr = [];
                While(date("m", $unix) == $month){ // keep looping/output of while it's correct month

                    Log::channel('stderr')->info('1 '.date("d/m/Y", $unix-86400));
                    Log::channel('stderr')->info('2 '.date("d/m/Y", $unix));
                    //$arr[] = ['L:' => date("d/m/Y", $unix-86400), 'D:' => date("d/m/Y", $unix)];
                    $unix = $unix + (86400*7);
                }
                Log::channel('stderr')->info('3 '.date("d/m/Y", strtotime("last day of ".$year . "-" . $month)));
                //Log::channel('stderr')->info($arr);



                ini_set('max_execution_time', -1);



                $materialiShhet[]= ['Material','Quantity','Product Codes'];
                $reportSheepLabel = [];
                $filter = '-1 Months';
                $reportSheep[]= [Date('Y-M',strtotime($filter))];
                $month = date('m',strtotime($filter));
                $t = substr($month,0,1);
                $titolo = Date('Y-m-d H:i',strtotime($filter));
                $codici = ['AD-CP','MC-CP','F6-NC','FV-CP','AR-NC','F7-NC','MC-NC','FV-NC','MM-NC','DP-CP','FT-CP','FT-NC','AD-NC','BR-NC',''];

                foreach ($codici as $codice){
                    $resultMateriali = Gp::reportCodMerceologico($codice,Date('Y',strtotime($filter)), Date('m',strtotime($filter)));
                    //Log::channel('stderr')->info($resultMateriali->count());

                    $reportSheepLabel[] =[$codice];
                    $reportSheep[] = [$resultMateriali->get()->sum('quantita')];
                    foreach ($resultMateriali->get() as $materiale)
                        $materialiShhet[] = [$materiale->cdProdotto,(float)str_replace('.',',',$materiale->quantita),$codice];
                }

                //env('ID_GOOGLE_MAGAZZINI')
                $fileReprot = GoogleDrive::search('1ahSiH3d_pQBdFp2r5Xe0TJFWaeNO571L', 'google', 'file', date('Y'), false);
                if(empty($fileReprot)){
                    $fileReprot = GoogleSheet::createSheet(date('Y'),'1ahSiH3d_pQBdFp2r5Xe0TJFWaeNO571L');
                    Sheets::spreadsheet($fileReprot);
                    Sheets::addSheet('Report');
                    Sheets::deleteSheet('Foglio1');
                    Sheets::sheet('Report')->range('A2')->update($reportSheepLabel);
                }
                else
                    Sheets::spreadsheet($fileReprot);

                $alphabet = range('A', 'Z');
                $month = ($t == '0' ? substr($month,1,2) : $month);
                $columnStart = $alphabet[$month];

                //Sheets::sheet('Report')->range($columnStart.'1')->update($reportSheep);

                Sheets::addSheet('Details '.$titolo);
                //Sheets::sheet('Details '.$titolo)->range('A1')->update($materialiShhet);


                Log::channel('stderr')->info('$usersNotifica');

                ini_set('max_execution_time', -1);
                $inv = Sheets::spreadsheet('1t8v4vu0BFiwoWR81ZiDyfbZeERmY1jydheAdgADAPz8')->sheet('Invio email')->all();
                $arr = [];

                $invio_email = [];
                foreach ($inv as $invio){
                    if(!empty($invio[0])){
                        $invio_email[] = [$invio[0]];
                        $arr[] = $invio[0];
                    }
                }
                $fornitori = QtSupplier::whereNotIn('codiceSap',$arr)->get();

                $o = 0;

                foreach ($fornitori as $fornitore){
                    if($o >= 20)
                        break;
                    if(filter_var($fornitore->email, FILTER_VALIDATE_EMAIL)){
                        Mail::send('emails/email_start_fornitori', [''], function ($message) use ($fornitore) {
                            $message
                                ->to($fornitore->email)
                                ->cc('certificazioni.metallurgica@stl.tech')
                                //->to(['gregorio.grande@stl.tech'])
                                ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a")
                                ->subject('Nuovo Portale Qualifica Fornitori / New Supplier Qualification Portal');
                        });
                        $invio_email[] = [$fornitore->codiceSap];
                        $o++;
                    }else{
                        $invio_email[] = [$fornitore->codiceSap,$fornitore->email,'Error Email'];
                    }

                }

                Sheets::sheet('Invio email')->update($invio_email);


                $users = Sheets::spreadsheet('1g9OrNG9eeNDKE1CofaDKtb0iMFIUnxGmqs8ROX86T_0')->sheet('Utenti')->all();

                foreach ($users as $user){
                    if(empty($user[0]))
                        continue;

                    $obj = QtSupplier::where('codiceSap',$user[0])->first();
                    if(!empty($obj->id)){
                        $us = QtSupplierUser::where('supplier_id',$obj->id)->where('email',strtolower($user[2]))->first();
                            if(!empty($us->id))
                                continue;

                        Log::channel('stderr')->info('Fornitore: ');
                        Log::channel('stderr')->info($obj->ragioneSociale);
                        Log::channel('stderr')->info($obj->id);

                        $upplierUser = new QtSupplierUser();
                        $upplierUser->nome = ($user[1] != '-' ? ucwords(strtolower($user[1])) : ucwords(strtolower($obj->ragioneSociale)));
                        $upplierUser->supplier_id = $obj->id;
                        $upplierUser->email = strtolower($user[2]);
                        $upplierUser->disattivo = true;
                        //$upplierUser->save();

                    }
                }

                try {

                    ini_set('max_execution_time', -1);

                    $inv = Sheets::spreadsheet('1t8v4vu0BFiwoWR81ZiDyfbZeERmY1jydheAdgADAPz8')->sheet('email fornitori')->all();
                    $arr = [];

                    $invio_email = [];
                    foreach ($inv as $invio){
                        if(!empty($invio[0])){
                            $invio_email[] = [$invio[0]];
                            $arr[] = $invio[0];
                        }
                    }

                    $fornitori = DB::connection('sqlsrv_fornitori')->table('suppliers')
                        ->select('suppliers.*')
                        ->whereNotIn('codiceSap',$arr)
                        //->where('id','A0002759-E487-45E1-B5C4-335F9C84E586')
                        ->get();

                    $o = 0;
                    foreach ($fornitori as $fornitore){
                        if($o >= 20)
                            break;

                        $emails =[];
                        if(filter_var($fornitore->email, FILTER_VALIDATE_EMAIL))
                            $emails[$fornitore->email] = $fornitore->email;
                        $users = DB::connection('sqlsrv_fornitori')->table('users')->where('supplier_id',$fornitore->id)->get();
                        foreach ($users as $user)
                            if(filter_var($user->email, FILTER_VALIDATE_EMAIL))
                                $emails[$user->email] = $user->email;

                        if($fornitore->nazione == 'IT')
                            $oggetto = 'Urgente - Raccolta dati Portale fornitori';
                        else
                            $oggetto = 'Urgent - Supplier Portal Data Collection';

                        if(count($emails) == 1){
                            $obj = DB::connection('sqlsrv_fornitori')->table('users')->where('email',$fornitore->email)->first();
                            if(empty($obj->id)){
                                $us = new QtSupplierUser();
                                $us->supplier_id = $fornitore->id;
                                $us->nome = $fornitore->ragioneSociale;
                                $us->email = $fornitore->email;
                                $us->disattivo = false;
                                $us->save();

                            }

                        }

                        if(count($emails)){
                            Mail::send('emails/email_avviso_portale_fornitore', [''], function ($message) use ($emails,$oggetto) {
                                $message
                                    ->to(array_values($emails))
                                    ->from("portale.metallurgica@stl.tech", "Metallurgica Bresciana S.p.a")
                                    ->subject($oggetto);
                            });


                            $invio_email[] = [$fornitore->codiceSap];
                            $o++;
                        }else{
                            $invio_email[] = [$fornitore->codiceSap,$fornitore->email,'Error Email'];
                        }
                    }
                } catch (\Exception $e) {
                    Sheets::sheet('email fornitori')->update($invio_email);
                }

                Sheets::sheet('email fornitori')->update($invio_email);


                ini_set('max_execution_time', -1);
                $workflows = DB::connection('mysql_old')->table('workflow_users')
                    ->join('workflows','workflow_users.Workflow','workflows.id')
                    ->select('workflow_users.*','workflows.created_at as workflow_data','workflows.commessa_name')
                    ->where('workflow_users.user',5)
                    ->whereNull('workflow_users.aprovato')
                    ->whereYear('workflows.created_at',2025)
                    ->whereMonth('workflows.created_at','03')
                    //->where('workflow_users.Workflow','51841adc-4621-4540-b977-7eb4935522a6')
                    ->get();

                $i = 0;
                foreach ($workflows as $workflow){
                    if ($i == 0)
                        break;
                    Log::channel('stderr')->info($workflow->commessa_name.' id: '.$workflow->Workflow);
                    $dt = explode("-",$workflow->workflow_data);
                    $data_firma = date("Y-m-d", strtotime(date("Y-m-t", strtotime($dt[0]."-".$dt[1]))));
                    $update = $data_firma.' 17:30:00';

                    DB::connection('mysql_old')->table("workflow_users")
                        ->where('Workflow',$workflow->Workflow)
                        ->where('user',$workflow->user)
                        ->update(['aprovato' => 1, 'data_view' => $update, 'updated_at' => $update]);

                    $approvatoriCommessa = DB::connection('mysql_old')->table('workflow_users')
                        ->where('Workflow',$workflow->Workflow)
                        ->whereNull('aprovato')
                        ->count();


                    if(!$approvatoriCommessa){
                        $ultimaApprovazione = DB::connection('mysql_old')->table('workflow_users')
                            ->where('Workflow',$workflow->Workflow)
                            ->orderBy('updated_at','desc')
                            ->first();

                        $data = explode(" ",$ultimaApprovazione->updated_at);
                        DB::connection('mysql_old')->table("workflows")
                            ->where('id',$workflow->Workflow)
                            ->update(['status' => 4, 'end_date' => $data[0], 'updated_at' => $ultimaApprovazione->updated_at]);
                    }else{
                        Log::channel('stderr')->info('Non chiusi: '.$workflow->commessa_name.' id: '.$workflow->Workflow);
                    }
                    $i++;
                }

                Log::channel('stderr')->info('ok '.$i);

                $cavi = Sheets::spreadsheet('1sPfeNIRiRikzkOPByAH7euWn8QnmfALge5sVn_-fe14')->sheet('Cavi')->all();
                $cavi_import = [];
                $p=1;
                foreach ($cavi as $cavo){
                    if (empty($cavo[7]) && empty($cavo[8]))
                        continue;
                    if(!empty($cavo[0])){
                        if(array_key_exists($cavo[0],  $cavi_import)){

                            $cavi_import[$cavo[0]]['Dettaglio'][] = [
                                'centro' => $cavo[7],
                                'materiale' => $cavo[8],
                                'descrizione' => $cavo[9],
                                'diametro' => $cavo[10],
                                'peso' => $cavo[11],
                                'ordinata' => $cavo[12],
                                'elementi' => $cavo[13],
                                'posizione' => $p,
                                'nota' => (!empty($cavo[14]) ? $cavo[14]:''),
                            ];
                        }
                        else{
                            $p = 1;
                            $cat = ToCategory::Where('categoria',$cavo[4])->first();
                            $cavi_import[$cavo[0]]['Cavo'] = [
                                'codice' => $cavo[0],
                                'categoria_id' => $cat->id,
                                'categoria' => $cat->categoria,
                                'descrizione' => $cavo[1],
                                'norma' => $cavo[5],
                                'disattivo' =>0,
                            ];
                            $cavi_import[$cavo[0]]['Dettaglio'][] = [
                                'centro' => $cavo[7],
                                'materiale' => $cavo[8],
                                'descrizione' => $cavo[9],
                                'diametro' => $cavo[10],
                                'peso' => $cavo[11],
                                'ordinata' => $cavo[12],
                                'elementi' => $cavo[13],
                                'posizione' => $p,
                                'nota' => (!empty($cavo[14]) ? $cavo[14]:''),
                            ];
                        }
                        $p++;
                    }
                }


                ToCable::WhereIn('codice',array_keys($cavi_import))->delete();

                foreach ($cavi_import as $cavo) {
                    $obj_c = new ToCable();
                    $obj_c->codice = $cavo['Cavo']['codice'];
                    $obj_c->categoria_id = $cavo['Cavo']['categoria_id'];
                    $obj_c->categoria = $cavo['Cavo']['categoria'];
                    $obj_c->descrizione = $cavo['Cavo']['descrizione'];
                    $obj_c->norma = $cavo['Cavo']['norma'];
                    //$obj_c->disattivo = 0;
                    $obj_c->save();

                    foreach ($cavo['Dettaglio'] as $row) {
                        $dettaglio = new ToCableStructure();
                        $dettaglio->cavo_id = $obj_c->id;
                        $dettaglio->centro = $row['centro'];
                        $dettaglio->materiale = $row['materiale'];
                        $dettaglio->descrizione = $row['descrizione'];
                        $dettaglio->diametro = (!empty($row['diametro']) ? str_replace(",",".",$row['diametro']):0);
                        $dettaglio->peso = (!empty($row['peso']) ? str_replace(",",".",$row['peso']):0);
                        $dettaglio->ordinata = (!empty($row['ordinata']) ? str_replace(",",".",$row['ordinata']) : 0);
                        $dettaglio->elementi = $row['elementi'];
                        $dettaglio->posizione = $row['posizione'];
                        $dettaglio->nota = $row['nota'];
                        $dettaglio->save();
                    }

                }


                //Log::channel('stderr')->info($cavi_import);
                dd();

                $preventivi = Sheets::spreadsheet('1sPfeNIRiRikzkOPByAH7euWn8QnmfALge5sVn_-fe14')->sheet('Preventivi 2')->all();
                $arr_prev = [];
                $tmp_c = [];
                foreach ($preventivi as $preventivo) {
                    if (!empty($preventivo[0]) && !empty($preventivo[6])) {
                        $cliente = DB::table('to_clients')->where('ragione_sociale', $preventivo[6])->first();
                        if (!empty($cliente->id))
                            $preventivo[6] = $cliente->id;
                        else{
                            $cliente = new ToClient();
                            $cliente->ragione_sociale = $preventivo[6];
                            $cliente->save();
                            $preventivo[6] = $cliente->id;
                        }


                        $arr_prev[$preventivo[0]] = $preventivo;

                    }
                }



                ini_set('memory_limit', '-1');
                ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
                set_time_limit(3600);
                $sheets = ['Dettaglio 2']; // 'Quatro','Cinque','Sei'
                $preventivi_creati = [];
                foreach ($sheets as $sheet){
                    $dettagli = Sheets::spreadsheet('1sPfeNIRiRikzkOPByAH7euWn8QnmfALge5sVn_-fe14')->sheet($sheet)->all();
                    foreach ($dettagli as $dettaglo) {
                        if (empty($dettaglo[0]))
                            continue;

                        if (empty($arr_prev[$dettaglo[0]]) || $arr_prev[$dettaglo[0]][6] == '9D07FE23-7B01-4A9A-949A-749D2ADEF5F7') {
                            //Log::channel('stderr')->info('Preventivo non Trovato: '.$dettaglo[0]);
                            continue;
                        }

                        if (!empty($preventivi_creati[$dettaglo[0]]))
                            $preventivo_id = $preventivi_creati[$dettaglo[0]]['id_preventivo'];
                        else {
                            $data_preventivo = explode("/", $arr_prev[$dettaglo[0]][1]);
                            if(empty($arr_prev[$dettaglo[0]][8]))
                                $arr_prev[$dettaglo[0]][8] = $arr_prev[$dettaglo[0]][1];
                            $data_rdo = explode("/", $arr_prev[$dettaglo[0]][8]);
                            $obj_preventivo = new ToQuote();
                            $obj_preventivo->user = 5;
                            $obj_preventivo->numero = $arr_prev[$dettaglo[0]][0];
                            if(!empty($data_preventivo[2]))
                                $obj_preventivo->data_preventivo = $data_preventivo[2] . '-' . $data_preventivo[1] . '-' . $data_preventivo[0];      // TODO data non nel formato corretto
                            $obj_preventivo->cu = str_replace(",",".",$arr_prev[$dettaglo[0]][3]);
                            $obj_preventivo->parametro = $arr_prev[$dettaglo[0]][4];
                            $obj_preventivo->cliente_id = $arr_prev[$dettaglo[0]][6];
                            $obj_preventivo->rdo = (!empty($arr_prev[$dettaglo[0]][7]) ? $arr_prev[$dettaglo[0]][7]: '');
                            if(!empty($data_rdo[2]))
                                $obj_preventivo->data_rdo = $data_rdo[2] . '-' . $data_rdo[1] . '-' . $data_rdo[0];   // TODO data non nel formato corretto
                            $obj_preventivo->nota = (!empty($arr_prev[$dettaglo[0]][10]) ? $arr_prev[$dettaglo[0]][10] : '');

                            $obj_preventivo->save(); #TODO SAVE

                            $preventivo_id = $obj_preventivo->id;
                            $preventivi_creati[$obj_preventivo->numero]['id_preventivo'] = $obj_preventivo->id;


                        }

                        if (empty($preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]])) {
                            $cavo = new ToQuoteCable();
                            $cavo->preventivo_id = $preventivo_id;
                            $cavo->codice = $dettaglo[3];
                            $cavo->descrizione = $dettaglo[4];
                            $cavo->metri = str_replace(",",".",$dettaglo[2]);
                            $cavo->scarto = str_replace(",",".",$dettaglo[6]);
                            $cavo->pezzatura = str_replace(",",".",$dettaglo[5]);
                            $cavo->parametro = str_replace(",",".",$dettaglo[10]);
                            $cavo->posizione = $dettaglo[1];
                            $cavo->save(); #TODO SAVE

                            $preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]] = $cavo->id;
                            $preventivi_creati[$dettaglo[0]]['pezzatura_' . $dettaglo[3]] = $cavo->pezzatura;
                            $preventivi_creati[$dettaglo[0]]['posizione_dettaglio_'.$dettaglo[3]] = 1;
                        }

                        $p =  $preventivi_creati[$dettaglo[0]]['posizione_dettaglio_'.$dettaglo[3]]++;


                        $costo = 0.00;
                        $costo_m = 0.0000;
                        if(!empty($dettaglo[17])){
                            $c = explode(",",$dettaglo[17]);
                            if(count($c) == 2)
                                $costo = $c[0].'.'.$c[1];
                            elseif(count($c) == 3)
                                $costo = $c[0].''.$c[1].'.'.$c[2];
                        }

                        if(!empty($dettaglo[18])){
                            $cm = explode(",",$dettaglo[18]);
                            if(count($cm) == 2)
                                $costo_m = $cm[0].'.'.$cm[1];
                            elseif(count($cm) == 3)
                                $costo_m = $cm[0].''.$cm[1].'.'.$cm[2];
                        }

                        $det_cav = new ToQuoteCableStructure();

                        $det_cav->cavo_id = $preventivi_creati[$dettaglo[0]]['cavo_' . $dettaglo[3]];
                        $det_cav->centro = $dettaglo[12];
                        $det_cav->materiale = $dettaglo[13];
                        $det_cav->descrizione = $dettaglo[14];
                        if(!empty($dettaglo[15]))
                            $det_cav->diametro = str_replace(",",".",$dettaglo[15]);
                        if(!empty($dettaglo[16]))
                            $det_cav->peso =  str_replace(",",".",$dettaglo[16]);
                        $det_cav->costo = $costo;
                        $det_cav->costo_materia_prima = $costo_m;
                        if(!empty($dettaglo[20]))
                            $det_cav->ordinata = str_replace(",",".",$dettaglo[20]);
                        $det_cav->elementi = (!empty($dettaglo[21]) ? str_replace(",",".",$dettaglo[21]): null);
                        $det_cav->ore_macchina = (!empty($dettaglo[24]) ? round(str_replace(",",".",$dettaglo[24]),1): 0);
                        $det_cav->nota = (!empty($dettaglo[23]) ? $dettaglo[23]:'');
                        $det_cav->costo_centro = (!empty($dettaglo[19]) ? str_replace(",",".",$dettaglo[19]) : 0);
                        $det_cav->costo_lavorazione = (!empty($dettaglo[22]) ? str_replace(",",".",$dettaglo[22]): 0);
                        $det_cav->posizione = $p;
                        //Log::channel('stderr')->info($dettaglo);
                        //Log::channel('stderr')->info($det_cav);
                        $det_cav->save(); #TODO SAVE

                        if ($dettaglo[12] == 'COLL') {
                            $diametro = str_replace(",",".",$dettaglo[15]);
                            $pezzatura = str_replace(",",".",$dettaglo[5]);
                            if(!empty($diametro) && !empty($cavo->metri)) {
                                $capacita = ($diametro * $diametro) * $pezzatura;
                                $bobina = ToReel::get_bobina(round($capacita,0));

                                $cavo = ToQuoteCable::where('id', $det_cav->cavo_id)->first();
                                if(!empty($bobina->id)){
                                    $cavo->bobina_id = $bobina->id;
                                    $cavo->bobina = $bobina->bobina;
                                    $cavo->bobina_numero = round($cavo->metri / $pezzatura,0);
                                    $cavo->peso = $bobina->peso;
                                    $cavo->m3 = $bobina->m3;
                                    $cavo->m3_totale = round($request->m3 * $cavo->bobina_numero, 2);
                                    $cavo->totale_costo_bobine = round($bobina->costo * $cavo->bobina_numero, 4);
                                    $cavo->costo_bobina = $bobina->costo;
                                }
                                $cavo->save();
                                if(!empty($bobina->id))
                                    $cavo->calcola_totali();
                            }
                        }
                    }
                }

                Log::channel('stderr')->info(count($arr_prev));



        $result = DB::connection('sqlsrv_root_gp')->table('Produzione as PRD')
            ->join('Risorse as R', 'R.IDRisorsa', '=', 'PRD.IDRis')
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(CASE WHEN F.IdCausaleFermo = 10 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F1,
SUM(CASE WHEN F.IdCausaleFermo = 14 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F5
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo IN (10,14) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FMac'),
                function ($join) {
                    $join->on('PRD.IDProduzione', '=', 'FMac.IDProduzione');
                })
            ->leftJoin(DB::raw('(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11,12) AND ISNULL(F.isAnnullato, 0) = 0
							   group by F.IDProduzione) AS FTMac'),
                function ($join) {
                    $join->on('PRD.IDProduzione', '=', 'FTMac.IDProduzione');
                })
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)


            ->whereBetween('PRD.DataOraInizio', ['2026-02-01 00:00:00:000', '2026-02-08 23:59:59:990'])
            ->select(
                'R.Modello',
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F5, 0), 0)) / 3600, 2)) As F5'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F1, 0), 0)) / 3600, 2)) As F1'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FTMac.TotalFermi, 0), 0)) / 3600, 2)) As FermiTotal'),
            //    DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2)) As ManodoperaH'),
                //DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine) * TotaleOperatori, 0)) / 3600, 2)) As ManodoperaCalcolataH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2)) As SchedaH'),
            )
            ->groupBy('R.Modello')
            ->get();

        Log::channel('stderr')->info($result);

        //Dispatch(new ImportMagazzinoBi());
        dd();
        try {
            ini_set('memory_limit', -1);
            ini_set('max_execution_time', 2900);
            $rows = Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY')->sheet('Magazzino')->all();
            //$now = strtotime($data);
            //$count_weeks = ceil((date("d", $now) - date("w", $now) - 1) / 7) + 1;
            DB::table('pr_warehouse_bis')->where('anno', $rows[0][9])->where('mese', $rows[0][10])
                ->where('settimana', $rows[0][12])
                ->delete();

            $data = '';
            $anno = '';
            $mese = '';
            $week = '';
            foreach ($rows as $row) {
                if (empty($data)) {
                    $data = $row[9] . '-' . $row[10] . '-' . $row[11];
                    $anno = $row[9];
                    $mese = $row[10];
                    $week = $row[12];
                }


                $t = $row;
                if ($row[0] == 'Material' || empty($row[0]))
                    continue;

                if (!empty($row[7])) {
                    $d = explode("/", $row[7]);
                    $data_ultimo_movimento = $d[2] . '-' . $d[1] . '-' . $d[0];
                    //$data_ultimo_movimento = \Illuminate\Support\Carbon::createFromFormat('m/d/Y', $row[7]) ->format('Y-m-d');
                } else
                    $data_ultimo_movimento = $data;//date('Y-m-d');

                // Log::channel('stderr')->info($data_ultimo_movimento);
                //$days_last_movement = (strtotime(date('Y-m-d')) - strtotime($data_ultimo_movimento) ) / 86400;

                $days_last_movement = (strtotime($data) - strtotime($data_ultimo_movimento)) / 86400;
                if ($days_last_movement <= 0)
                    $days_last_movement = 0;

                $obj = new PrWarehouseBi();
                $obj->id_material = null;
                $obj->materiale = $row[0];
                $obj->descrizione = $row[1];
                $obj->um = $row[3];
                $obj->quantita = str_replace(",", ".", str_replace(".", "", $row[2]));
                $obj->valore_uni = 0.00;
                if(!empty($row[4]))
                    $obj->valore_uni = str_replace(",", ".", str_replace(".", "", $row[4]));
                $obj->totole = 0.00;
                if($row[5] != "-")
                    $obj->totole = str_replace(",", ".", str_replace(".", "", $row[5]));
                $obj->categoria = '';
                $obj->data_ultimo_movimento = $data_ultimo_movimento;
                $obj->days_last_movement = round($days_last_movement, 0);
                $obj->range_last_moviment = '';
                $obj->anno = $anno;//date('Y');
                $obj->mese = $mese;//date('m');
                $obj->settimana = $week;//$count_weeks;
                $obj->verificato = false;

                $obj->save();

            }
            //Dispatch(new ImportMagazzinoBi());
            dd();
        } catch (\Exception $e) {
            Log::channel('stderr')->info($row);
            Log::channel('stderr')->info($e);

        }

        //Dispatch(new ImportMagazzinoBi());



        $us = ['23920511' => 'Vanni Fogliata', '23920619' =>'Chiara Carrera', '23920599' =>'Gianpaolo Vitarelli' ];
        $time = strtotime(date('Y-m-d').' -7 Day');
        $stratDate = date('Y-m-d', $time);

        $time = strtotime(date('Y-m-d').' -1 Day');
        $andDate = date('Y-m-d', $time);

        $objs = DB::table('pr_movements')
            ->select('um','user',DB::raw('SUM(quantita) as qty'),DB::raw('SUM(importo) as total'))
            ->whereBetween('data_pubblicazione',[$stratDate,$andDate])
            ->where('tipo_movimento','LIKE','5%')
            ->groupBy('um','user')
            ->get();

        $result = [];
        foreach ($objs as $obj){
            $user = DB::connection('mysql_old')->table('employees')
                ->select('nome','cognome')
                ->where('matricola',substr($obj->user, 3, 8))->first();
            if(!empty($user->nome))
                $user = $user->nome.' '.$user->cognome;
            elseif(!empty($us[$obj->user]))
                $user = $us[$obj->user];
            else
                $user = $obj->user;

            if(empty($result[$user])){
                $result[$user]['t_qty'] = 0;
                $result[$user]['t_total'] = 0;
            }

            if($obj->um == 'M'){
                if(empty($result[$user]['KM'])){
                    $result[$user]['KM']['qty'] = 0;
                    $result[$user]['KM']['total'] = 0;
                }
                $result[$user]['KM']['qty']+= round((float)str_replace("-","",$obj->qty) / 1000, 3);
                $result[$user]['KM']['total']+= (float)str_replace("-","",$obj->total);
            }
            else{
                if(empty($result[$user][$obj->um])){
                    $result[$user][$obj->um]['qty'] = 0;
                    $result[$user][$obj->um]['total'] = 0;
                }
                $result[$user][$obj->um]['qty']+= (float)str_replace("-","",$obj->qty);
                $result[$user][$obj->um]['total']+= (float)str_replace("-","",$obj->total);


            }

            $result[$user]['t_qty']+= (float)str_replace("-","",$obj->qty);
            $result[$user]['t_total']+= (float)str_replace("-","",$obj->total);

        }

        $users = Utility::users_notify(['pr_inventory_adjustment']);
dd();
        Mail::send('emails/email_scrap', ['users' => $result, 'inizio' => $stratDate, 'fine' => $andDate], function ($message) use($users){
            $message
                ->to(['walter.forzanini@stl.tech','antonio.guerreschi@stl.tech'])
                ->subject('Scrap - Metallurgica Bresciana');
        });
        dd();
        $result = [
            'inizio' => $stratDate,
            'fine' => $andDate,
            'rows' => $objs
        ];
        dd();


        Mail::send('emails/email_inventory_adjustment', ['data' => $result], function ($message) use($users){
            $message
                ->to($users)
                ->subject('Inventory Adjustment - Metallurgica Bresciana');
        });

        ini_set('memory_limit', '3048M');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
        //$files = GoogleDrive::search('0AARdHHHpnqtAUk9PVA','google','file','Movimenti.XLSX',false);
        $rows = Sheets::spreadsheet('1ipYOc5wPAGHmnK4gaEXSgihLvlSoT80_stsfcSOnOLA')->sheet('Sheet1')->all();
        //Log::channel('stderr')->info($rows);
        $i = 1;
        //Log::channel('stderr')->info(count($rows));
        $time = strtotime(date('Y-m-d').' -7 Day');
        foreach ($rows as $row) {

            if($i == 1){
                $i++;
                continue;
            }

            if(empty($row[0]))
                continue;

            $data_inserimento = strtotime($row[13]);

            if (!empty($row[0]) && $data_inserimento >= $time) {
                $obj = New PrMovement();
                $obj->materiale = $row[0];
                $obj->descrizione = $row[1];
                $quantita = str_replace(',','.', str_replace('.','',$row[2]));
                $obj->quantita = (strpos($quantita, ".") === FALSE ?  $quantita.'.000':$quantita);
                $import = str_replace(',','.',str_replace('.','',$row[3]));
                $obj->importo = (strpos($import, ".") === FALSE ?  $import.'.00':$import);
                $obj->um = $row[4];
                $obj->lotto = $row[5];
                $obj->plant = $row[6];
                $obj->posizione_archiviazione = $row[7];
                $obj->tipo_movimento = $row[8];
                $obj->special_stock = $row[9];
                $obj->documento_materiale = $row[10];
                $data_pubblicazione = explode("/", $row[11]);
                $obj->data_pubblicazione = $data_pubblicazione[2] . '-' . $data_pubblicazione[0] . '-' . $data_pubblicazione[1];
                $data_documento = explode("/", $row[12]);
                $obj->data_documento = $data_documento[2] . '-' . $data_documento[0] . '-' . $data_documento[1];
                $data_inserimento = explode("/", $row[13]);
                $obj->data_inserimento = $data_inserimento[2] . '-' . $data_inserimento[0] . '-' . $data_inserimento[1];
                $obj->testo_movimento = $row[14];
                $obj->user = $row[15];
                $obj->save();

            }
            $i++;
        }
        dd();




        $month = [];
        for($m=1;$m<=12;$m++){
            $monthName = date('F', mktime(0, 0, 0, $m, 10));
            $weeks = $this->getWeek('2026-'.$m.'-01');
            $month[$monthName]['JACK']['t_scarto'] = 0;
            $month[$monthName]['BUF']['t_scarto'] = 0;
            $month[$monthName]['SZD']['t_scarto'] = 0;
            $month[$monthName]['Consumi'] = 0;
            $tDiff = 0.0;
            foreach ($weeks as $k => $week){
                $scarti = DB::table('pr_movements')
                    ->join('pr_materials','pr_movements.materiale','pr_materials.materiale')
                    ->select(
                        DB::raw('SUM(pr_movements.importo) as totale'),
                        DB::raw("CASE WHEN categorie LIKE '%-BUF-%' THEN 'BUF' WHEN categorie LIKE '%-JACK-%' THEN 'JACK' WHEN categorie LIKE '%-SZD-%' THEN 'SZD' END as t")
                        )
                    ->where('tipo_movimento','LIKE', '5%')
                    ->Where(function ($query)  {
                        $query->Where('categorie','LIKE', '%-JACK-%')
                            ->orWhere('categorie','LIKE', '%-BUF-%')
                            ->orWhere('categorie','LIKE', '%-SZD-%');
                    })
                    ->whereBetween('data_documento',['2026-01-12','2026-01-18'])
                    ->groupBy('categorie')->get();

                $scarti = collect($scarti);

                //Log::channel('stderr')->info($week['start'].' - '.$week['end']);
                //Log::channel('stderr')->info('Scarti:');
                //Log::channel('stderr')->info($scarti);
               //Log::channel('stderr')->info('Consumi');
               //Log::channel('stderr')->info($consumi->totale);


                $consumi = DB::table('pr_movements')
                    ->join('pr_materials','pr_movements.materiale','pr_materials.materiale')
                    ->select(
                        DB::raw('SUM(pr_movements.importo) as totale'),
                    )
                    ->Where(function ($query)  {
                        $query->Where('tipo_movimento','LIKE', '2%');
                    })
                    ->whereBetween('data_documento',[$week['start'],$week['end']])
                    ->Where(function ($query)  {
                        $query->where('categorie','LIKE', '%-FIBER-%')
                            ->orWhere('categorie','LIKE', '%-RAWOFC-%');
                    })
                    ->first();

                $consumo = 0;
                if(!empty($consumi->totale)){
                    $consumo = round(str_replace("-","",$consumi->totale));
                    $month[$monthName]['Consumi']+=$consumo;
                    $month[$monthName][explode('-',$week['start'])[2].'-'.explode('-',$week['end'])[2]]['Consimi'] = $consumo;
                }

                $scarti['JACK'] = $scarti->where('t','JACK')->sum('totale');
                if(!empty($scarti['JACK'])){
                    $scarto = round(str_replace("-","",$scarti['JACK']));
                    $month[$monthName]['JACK']['t_scarto']+= $scarto;
                    $month[$monthName][$k]['JACK']['Scarto'] = $scarto;
                    if(!empty($month[$monthName][explode('-',$week['start'])[2].'-'.explode('-',$week['end'])[2]]['Consimi']))
                        $month[$monthName][$k]['JACK']['Dif'] = round(($scarto  / ($consumo - $scarto))  * 100, 1);
                }else
                    $month[$monthName][$k]['JACK']['Dif'] = 0.0;


                $scarti['BUF'] = $scarti->where('t','BUF')->sum('totale');
                if(!empty($scarti['BUF'])){
                    $scarto = str_replace("-","",$scarti['BUF']);
                    $month[$monthName]['BUF']['t_scarto']+= $scarto;
                    $month[$monthName][$k]['BUF']['Scarto'] = $scarto;
                    if(!empty($month[$monthName][explode('-',$week['start'])[2].'-'.explode('-',$week['end'])[2]]['Consimi']))
                        $month[$monthName][$k]['BUF']['Dif'] = round(($scarto  / ($consumo - $scarto))  * 100, 1);
                }else
                    $month[$monthName][$k]['BUF']['Dif'] = 0.0;

                $scarti['SZD'] = $scarti->where('t','SZD')->sum('totale');
                if(!empty($scarti['SZD'])){
                    $scarto = str_replace("-","",$scarti['SZD']);
                    $month[$monthName]['SZD']['t_scarto']+= $scarto;
                    $month[$monthName][$k]['SZD']['Scarto'] = $scarto;
                    if(!empty($month[$monthName][explode('-',$week['start'])[2].'-'.explode('-',$week['end'])[2]]['Consimi']))
                        $month[$monthName][$k]['SZD']['Dif'] = round(($scarto  / ($consumo - $scarto))  * 100, 1);
                }else
                    $month[$monthName][$k]['SZD']['Dif'] = 0.0;

            }

            //$month[$m][explode('-',$week['start'])[2].'-'.explode('-',$week['end'])[2]]['SZD']['t_scarto'] = 0;

        }

        $result = DB::connection('sqlsrv_root_gp')->table('200134_MB.dbo.Produzione as PRD')
            ->join('Risorse as R','R.IDRisorsa','=','PRD.IDRis')
            ->leftJoin(DB::raw("(SELECT idScheda, [Dichiarazione 1] as Dichiarazione1, [Dichiarazione 2] as Dichiarazione2, [Dichiarazione 1] as Dichiarazione3, [Dichiarazione 4] as Dichiarazione4
                               FROM (SELECT idScheda, Operatore AS A, IDDipendente FROM ElencoOperatoriSchede INNER JOIN Produzione as P on P.IDProduzione = idScheda ) 
            src PIVOT (MAX(IDDipendente) FOR A IN ([Dichiarazione 1], [Dichiarazione 2], [Dichiarazione 3], [Dichiarazione 4])) pvt) AS PVT"),
                function($join)
                {
                    $join->on('PRD.IDPRODUZIONE', '=', 'PVT.idScheda');
                })
            ->leftJoin('Dipendenti As Dip1','PVT.Dichiarazione1','Dip1.IDImpiegato')
            ->leftJoin('Dipendenti As Dip2','PVT.Dichiarazione2','Dip2.IDImpiegato')
            ->leftJoin('Dipendenti As Dip3','PVT.Dichiarazione3','Dip3.IDImpiegato')
            ->leftJoin('Dipendenti As Dip4','PVT.Dichiarazione4','Dip4.IDImpiegato')
            ->leftJoin('AGGDB_Produzione_Schede_Ricalcolate as SR','PRD.IDProduzione','SR.idscheda')
            ->leftJoin(DB::raw("(SELECT O.idScheda, SUM(O.DurataCalcolata) * 3600 AS DurataCalcolataOperatoriSec, COUNT(DISTINCT O.IdDipendente) AS TotaleOperatori FROM Operatori_Prod_Tbl O WHERE ISNULL(O.Annulla, 0) = 0 AND O.IdDipendente != 0 GROUP BY O.idScheda) AS D
		on CASE WHEN ISNULL(SR.dacarica, 0) = 0 THEN PRD.IDProduzione ELSE PRD.IDSchedaPrdOrdineAcc END = D.idScheda
		left join (SELECT F.IDProduzione, SUM(CASE WHEN F.IdCausaleFermo = 10 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F1,
		SUM(CASE WHEN F.IdCausaleFermo = 14 THEN DATEDIFF(ss, F.DOInizio, F.DOFine) ELSE 0 END) as F5
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo IN (10,14) AND ISNULL(F.isAnnullato, 0) = 0 AND P.Confermato = 1 AND P.Significativo = 1 AND P.IdSchedaPrdOrdineAcc = 0
							   group by F.IDProduzione) AS FMac"),
                function($join)
                {
                    $join->on('PRD.IDPRODUZIONE', '=', 'FMac.IDProduzione');
                })
            ->leftJoin(DB::raw("(SELECT F.IDProduzione, SUM(DATEDIFF(ss, F.DOInizio, F.DOFine)) as TotalFermi
                               FROM Fermi F INNER JOIN Produzione P ON F.IdProduzione = P.IDProduzione INNER JOIN
                               Causali_Fermo CSLF ON F.IdCausaleFermo = CSLF.IDCausaleFermo 
                               WHERE ISNULL(CSLF.EscStt, 0) = 0 AND F.IdCausaleFermo NOT IN (11,12) AND ISNULL(F.isAnnullato, 0) = 0 AND P.Confermato = 1 AND P.Significativo = 1 AND P.IdSchedaPrdOrdineAcc = 0
							   group by F.IDProduzione) AS FTMac"),
                function($join)
                {
                    $join->on('PRD.IDPRODUZIONE', '=', 'FTMac.IDProduzione');
                })
            ->select(
                'R.Modello AS Macchina',
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F5, 0), 0)) / 3600, 2)) as F5'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FMac.F1, 0), 0)) / 3600, 2)) as F1'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(FTMac.TotalFermi, 0), 0)) / 3600, 2)) As FermiTotal'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(ISNULL(D.DurataCalcolataOperatoriSec, 0), 0)) / 3600, 2)) As ManodoperaH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine) * TotaleOperatori, 0)) / 3600, 2)) As ManodoperaCalcolataH'),
                DB::raw('SUM(ROUND(CONVERT(FLOAT, ROUND(DATEDIFF(ss, PRD.DataOraInizio, PRD.DataOraFine), 0)) / 3600, 2)) As SchedaH')
            )
            ->where('PRD.Confermato', 1)
            ->where('PRD.Significativo', 1)
            ->where('PRD.IdSchedaPrdOrdineAcc', 0)
            ->whereBetween('PRD.DataOraInizio', ['2025-04-01 00:00:00.000' , '2026-03-31 23:59:59.999'])
            ->groupBy('R.Modello')
            ->orderBy('Macchina', 'asc') //order in descending order
            ->get();

        $macchineObjs = DB::connection('sqlsrv_root_gp')->table('cln200134_risorsecentrilavoro')->get();
        $macchine = [];
        foreach($macchineObjs as $obj)
            $macchine[$obj->Modello] = $obj->cdCentro;

        $costiObjs = DB::connection('mysql_old')->table('machine_labor_costs')->where('date_import','2022-03-03')->get();
        $costi = [];
        foreach($costiObjs as $cost)
            $costi[$cost->machine] = ['costo_macchina'=>$cost->cost, 'costo_manodopera' => $cost->manpawer_cost];

        $items = [];
        $sheet[] = ['Macchina','Ore Macchina','Fermi Totali','F5','F1','Totale Macchina','Costo Macchina','Totale Costo Macchina','Manodopera H','Fermi Manodopera','F5','F1','totale Man','Costo Manodopera','Totale costo manodopera'];
        foreach ($result as $r){
            $costoManodopera = $costoMachina = '';
            if(!empty($costi[str_replace(" ","", $macchine[$r->Macchina])]['costo_macchina']))
                $costoMachina = $costi[str_replace(" ","", $macchine[$r->Macchina])]['costo_macchina'];
            if(!empty($costi[str_replace(" ","", $macchine[$r->Macchina])]['costo_manodopera']))
                $costoManodopera = $costi[str_replace(" ","", $macchine[$r->Macchina])]['costo_manodopera'];

            $totOreMacchina = round($r->SchedaH,2)-round($r->FermiTotal,2);
            $totCostoManodopera = $totCostoMacchina = '';
            if($costoMachina)
                $totCostoMacchina = $totOreMacchina*$costoMachina;
            $fermiMan = round(($r->ManodoperaH/$r->SchedaH)*$r->FermiTotal,2);
            $fermiManF1 = round(($r->ManodoperaH/$r->SchedaH)*$r->F1,2);
            $fermiManF5 = round(($r->ManodoperaH/$r->SchedaH)*$r->F5,2);
            $totOreManodopera = round($r->ManodoperaH - $fermiMan ,2);
            if($costoManodopera)
                $totCostoManodopera = $totOreManodopera*$costoManodopera;

            $sheet[] = [$r->Macchina,round($r->SchedaH,2),round($r->FermiTotal,2),round($r->F5,2), round($r->F1,2),$totOreMacchina,$costoMachina,$totCostoMacchina,round($r->ManodoperaH,2),$fermiMan,$fermiManF5,$fermiManF1,$totOreManodopera,$costoManodopera,$totCostoManodopera];
            $items[] = [
                'Macchina' => $r->Macchina,
                'OreMacchina' => round($r->SchedaH,2),
                'FermiMacchina' => round($r->FermiTotal,2),
                'F1' => round($r->F1,2),
                'F5' => round($r->F5,2),
                'TotaleOreMacchina' => $totOreMacchina,
                'CostoMacchina' => $costoMachina,
                'TotaleCostoMecchina' => $totCostoMacchina,
                'OreManodopera' => round($r->ManodoperaH,2),
                'FermiManoDopera' => $fermiMan,
                'F1Man' => $fermiManF1,
                'F5Man' => $fermiManF5,
                'TotaleOreManodopera' => $totOreManodopera,
                'CostoManodopera' => $costoManodopera,
                'TotaleCostoManodopera' => $totCostoManodopera
            ];
        }
        //Sheets::spreadsheet('1rgxH9bTBGohmdGQGiNzelrsoV0g-R5PbHQB7cnrBGhg');
        //Sheets::sheet('Sheet1')->update($sheet);


        $items = collect($items)->sortBy('Macchina');
        $paginated = $items->forPage(0, 3);
       // $page = LengthAwarePaginator::resolveCurrentPage('page');

        Log::channel('stderr')->info($paginated);


        ini_set('memory_limit', '3048M');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
        $cavi = Sheets::spreadsheet('1eJQkh6tS3QoW8ABR1lIAVmimx9w3bns5m6_vjgdM6Pc')->sheet('Sheet1')->all();
        $cavi_import = [];
        $p=1;
        foreach ($cavi as $cavo){
            if (empty($cavo[7]) && empty($cavo[8]))
                continue;
            if(!empty($cavo[0])){
                if(array_key_exists($cavo[0],  $cavi_import)){

                    $cavi_import[$cavo[0]]['Dettaglio'][] = [
                        'centro' => $cavo[7],
                        'materiale' => $cavo[8],
                        'descrizione' => $cavo[9],
                        'diametro' => (!empty($cavo[10]) ? $cavo[10]:0.00),
                        'peso' => (!empty($cavo[11]) ? $cavo[11]:0.00),
                        'ordinata' => (!empty($cavo[12]) ? $cavo[12]:''),
                        'elementi' => (!empty($cavo[13]) ? $cavo[13]:''),
                        'posizione' => $p,
                        'nota' => (!empty($cavo[14]) ? $cavo[14]:''),
                    ];
                }
                else{
                    $p = 1;
                    $cat = ToCategory::Where('categoria',$cavo[4])->first();
                    if(empty($cat->id)){
                        $cat = new ToCategory();
                        $cat->categoria = $cavo[4];
                        $cat->modulo = 1;
                        $cat->save();
                    }

                    $cavi_import[$cavo[0]]['Cavo'] = [
                        'codice' => $cavo[0],
                        'categoria_id' => $cat->id,
                        'categoria' => $cat->categoria,
                        'descrizione' => $cavo[1],
                        'norma' => $cavo[5],
                        'disattivo' =>0,
                    ];

                    $cavi_import[$cavo[0]]['Dettaglio'][] = [
                        'centro' => $cavo[7],
                        'materiale' => $cavo[8],
                        'descrizione' => $cavo[9],
                        'diametro' => (!empty($cavo[10]) ? str_replace(",",".",$cavo[10]):0.00),
                        'peso' => (!empty($cavo[11]) ? str_replace(",",".",$cavo[11]):0.00),
                        'ordinata' => (!empty($cavo[12]) ? str_replace(",",".",$cavo[12]):null),
                        'elementi' => (!empty($cavo[13]) ? str_replace(",",".",$cavo[13]):null),
                        'posizione' => $p,
                        'nota' => (!empty($cavo[14]) ? $cavo[14]:''),
                    ];
                }
                $p++;
            }
        }

        //Log::channel('stderr')->info($cavi_import);


        //ToCable::WhereIn('codice',array_keys($cavi_import))->delete();

        $z = 1;
        foreach ($cavi_import as $cavo) {

            $obj = ToCable::Where('codice',$cavo['Cavo']['codice'])->first();
            if(empty($obj->id)){
                $obj_c = new ToCable();
                $obj_c->codice = $cavo['Cavo']['codice'];
                $obj_c->categoria_id = $cavo['Cavo']['categoria_id'];
                $obj_c->categoria = $cavo['Cavo']['categoria'];
                $obj_c->descrizione = $cavo['Cavo']['descrizione'];
                $obj_c->norma = $cavo['Cavo']['norma'];
                $obj_c->disattivo = false;
                $obj_c->save();
                foreach ($cavo['Dettaglio'] as $row) {
                    $dettaglio = new ToCableStructure();
                    $dettaglio->cavo_id = $obj_c->id;
                    $dettaglio->centro = $row['centro'];
                    $dettaglio->materiale = $row['materiale'];
                    $dettaglio->descrizione = $row['descrizione'];
                    $dettaglio->diametro = (!empty($row['diametro']) ? str_replace(",",".",$row['diametro']):0);
                    $dettaglio->peso = (!empty($row['peso']) ? str_replace(",",".",$row['peso']):0);
                    $dettaglio->ordinata = (!empty($row['ordinata']) ? str_replace(",",".",$row['ordinata']) : 0);
                    $dettaglio->elementi = $row['elementi'];
                    $dettaglio->posizione = $row['posizione'];
                    $dettaglio->nota = $row['nota'];
                     $dettaglio->save();
                }
            }else{
                $z++;
                Log::channel('stderr')->info($z);
            }


        }


        //Log::channel('stderr')->info($cavi_import);
        dd();


        ini_set('memory_limit', '3048M');
        ini_set('max_execution_time', 3600); // 3600 seconds = 60 minutes
        set_time_limit(3600);
        //$files = GoogleDrive::search('0AARdHHHpnqtAUk9PVA','google','file','Movimenti.XLSX',false);
        $rows = Sheets::spreadsheet('1NokD2ur4U7GQxckGw6ICsIfBeFh9Rwh9AHjKsy3GveE')->sheet('Sheet1')->all();
        //Log::channel('stderr')->info($rows);
        $i = 1;
        //Log::channel('stderr')->info(count($rows));
        $time = strtotime(date('Y-m-d').' -7 Day');


        $obj = DB::table('pr_movements')
            ->leftJoin('pr_materials','pr_movements.materiale','pr_materials.materiale')
            ->select(DB::raw('SUM(quantita) as totale'))
            //->select('pr_movements.materiale','pr_movements.quantita')
            ->where('pr_movements.um','<>','M')
            ->where('tipo_movimento','LIKE','2%')
            ->Where(function ($query) {
                $query ->where('pr_materials.categorie','LIKE','%-RAWWKCC-%')
                    ->orWhere('pr_materials.categorie','LIKE','%-SFCCW-%');
            })
            ->whereYear('data_documento',date('Y',$time))
            ->whereMonth('data_documento',date('m',$time))
            ->first();

        Log::channel('stderr')->info(date('Y',$time));
        Log::channel('stderr')->info(date('m',$time));

        $spreadsheet = new Google_Service_Sheets_SpreadsheetProperties([
            'properties' => [
                'locale' => 'it_IT',
            ]
        ]);

        $service = Sheets::getService();
        $service->spreadsheets->get('')->setProperties($spreadsheet);

        //$ar = Sheets::spreadsheet('1HvaMXcixhMBfcm-Q5paZH-8o4ejFbul_JIo5Kcc1AqA');
        //Sheets::sheet('Movimenti Rame')->update($r);

        //$res = Sheets::spreadsheetProperties();
        Log::channel('stderr')->info((array)$ar);

        dd();
        foreach ($rows as $row) {

            if($i == 1){
                $i++;
                continue;
            }

            if(empty($row[0]))
                continue;

            $data_inserimento = strtotime($row[13]);

            if (!empty($row[0])) {
            //if (!empty($row[0]) && $data_inserimento >= $time) {
                $obj = New PrMovement();
                $obj->materiale = $row[0];
                $obj->descrizione = $row[1];
                $quantita = str_replace(',','.', str_replace('.','',$row[2]));
                $obj->quantita = (strpos($quantita, ".") === FALSE ?  $quantita.'.000':$quantita);
                $import = str_replace(',','.',str_replace('.','',$row[3]));
                $obj->importo = (strpos($import, ".") === FALSE ?  $import.'.00':$import);
                $obj->um = $row[4];
                $obj->lotto = $row[5];
                $obj->plant = $row[6];
                $obj->posizione_archiviazione = $row[7];
                $obj->tipo_movimento = $row[8];
                $obj->special_stock = $row[9];
                $obj->documento_materiale = $row[10];
                $data_pubblicazione = explode("/", $row[11]);
                $obj->data_pubblicazione = $data_pubblicazione[2] . '-' . $data_pubblicazione[0] . '-' . $data_pubblicazione[1];
                $data_documento = explode("/", $row[12]);
                $obj->data_documento = $data_documento[2] . '-' . $data_documento[0] . '-' . $data_documento[1];
                $data_inserimento = explode("/", $row[13]);
                $obj->data_inserimento = $data_inserimento[2] . '-' . $data_inserimento[0] . '-' . $data_inserimento[1];
                $obj->testo_movimento = $row[14];
                $obj->user = $row[15];
                //$obj->save();

            }
            $i++;
        }

        $obj = DB::table('pr_movements')
            ->leftJoin('pr_materials','pr_movements.materiale','pr_materials.materiale')
            ->select(DB::raw('SUM(quantita) as totale'))
            //->select('pr_movements.materiale','pr_movements.quantita')
            ->where('pr_movements.um','<>','M')
            ->where('tipo_movimento','LIKE','2%')
            ->Where(function ($query) {
                $query ->where('pr_materials.categorie','LIKE','%-RAWWKCC-%')
                    ->orWhere('pr_materials.categorie','LIKE','%-SFCCW-%');
            })
            ->whereYear('data_documento',2026)
            ->whereMonth('data_documento',4)
            ->first();

        DB::connection('sqlsrv_root_gp')->table('plant_costs')
            ->where('year',date('Y',$time))
            ->where('month',date('m',$time))
            ->update(['cc_kg' => $obj->totale]);
        dd();

        $obj = DB::table('pr_movements')
            ->leftJoin('pr_materials','pr_movements.materiale','pr_materials.materiale')
            ->select(DB::raw('SUM(quantita) as totale'))
            //->select('pr_movements.materiale','pr_movements.quantita')
            ->where('pr_movements.um','<>','M')
            ->where('tipo_movimento','LIKE','2%')
            ->Where(function ($query) {
                    $query ->where('pr_materials.categorie','LIKE','%-RAWWKCC-%')
                        ->orWhere('pr_materials.categorie','LIKE','%-SFCCW-%');
            })
            ->whereYear('data_documento',2026)
            ->whereMonth('data_documento',4)
            ->first();

        $r[]= ['Materiale','quantità'];
        foreach ($obj as $row)
            $r[]= [$row->materiale,(float)$row->quantita];

        Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
        //Sheets::sheet('Movimenti Rame')->update($r);
        Sheets::sheetProperties();

        //

*/


        // 1. Evitiamo che lo script vada in timeout se ci sono molti PDF o molte chiamate a Gemini
        set_time_limit(300); // Imposta il limite a 5 minuti

        // 2. Definiamo il percorso assoluto della cartella usando public_path()
        // Aggiungiamo '*.pdf' alla fine per dire a PHP di cercare SOLO i file con estensione .pdf
        $searchPattern = storage_path('app/documenti/*.pdf');

        // 3. Recuperiamo la lista di tutti i percorsi dei file PDF
        $files = glob($searchPattern);

        // Se non ci sono file o la cartella è vuota
        if (empty($files)) {
            Log::warning("Nessun file PDF trovato nella cartella: " . storage_path('app/documenti/'));
            return response()->json(['messaggio' => 'Nessun file trovato da elaborare.'], 200);
        }

        $risultati = [];
        $documentReaderService = new GeminiAiService();

        // 4. Ciclo foreach per analizzare ogni singolo file trovato
        foreach ($files as $absoluteFilePath) {

            // Estraiamo solo il nome del file (es. "fattura_scansione.pdf") per i log e la risposta
            $nomeFile = basename($absoluteFilePath);

            Log::info("Inizio elaborazione file commessa: " . $nomeFile);

            try {
                // 5. Chiamata al tuo servizio (Spatie se nativo, Gemini se immagine/scansione)
                //$promptCommessa = "Trova il numero di commessa. Deve essere solo numerica, lunga 10 cifre e iniziare per 46. Rispondi solo con il numero o con NON TROVATO.";

                $promptCommessa = 'Sei un assistente di estrazione dati strutturati. Analizza i documenti forniti seguendo queste istruzioni tassative:

1. **Filtro Pagine e Riconoscimento**:
   * Una pagina è considerata VALIDA solo se contiene la dicitura esatta "DOCUMENTO DI TRASPORTO".
   * In queste pagine, identifica il Numero di Commessa (10 cifre, inizia con 46) e il Numero del DDT (10 cifre, inizia con 516).
   * Una pagina è considerata DIVERSA (da scartare) se non contiene la dicitura "DOCUMENTO DI TRASPORTO" o se non presenta la coppia di codici richiesta.

2. **Formato della Risposta**:
   * Restituisci esclusivamente un oggetto JSON con due liste distinte strutturato esattamente così:
     {
       "documenti_validi": [
         {"pagina": 1, "commessa": "4612345678", "ddt": "5161234567"}
       ],
       "pagine_scartate": [2, 3, 5]
     }
   * Se l\'intero file non contiene assolutamente nulla (nessun documento valido e nessuna pagina diversa), rispondi unicamente con la stringa: NON TROVATO.
   * Non includere i markdown del codice (no ```json o ```text), non aggiungere introduzioni, spiegazioni o testo di contorno. Sii totalmente sintetico.';

                $documentReaderService = new GeminiAiService();
                $numeroDocumento = $documentReaderService->analizzaFile(
                    filePath: $absoluteFilePath,
                    prompt: $promptCommessa,
                    mimeType: 'application/pdf'
                );

                //$numeroDocumento = $documentReaderService->estraiNumeroDocumento($absoluteFilePath);

                if ($numeroDocumento) {
                    Log::info("File $nomeFile elaborato con successo. Commesse: ");
                    Log::info($numeroDocumento);

                    $risultati[] = [
                        'file' => $nomeFile,
                        'stato' => 'Successo',
                        //'numero' => $numeroDocumento
                    ];

                    // [FACOLEATIVO] Qui puoi inserire la tua logica per salvare il dato nel DB
                    // es. Commessa::where('file_name', $nomeFile)->update(['numero' => $numeroDocumento]);

                } else {
                    Log::warning("Impossibile trovare il numero nel file commessa: " . $nomeFile);
                    $risultati[] = [
                        'file' => $nomeFile,
                        'stato' => 'Non Trovato',
                        'numero' => null
                    ];
                }

            } catch (Exception $e) {
                Log::error("Errore critico durante l'elaborazione del file $nomeFile: " . $e->getMessage());
                $risultati[] = [
                    'file' => $nomeFile,
                    'stato' => 'Errore',
                    'errore' => $e->getMessage()
                ];
            }
        }




        Log::channel('stderr')->info($risultati);

        dd();
        set_time_limit(300); // Imposta il limite a 5 minuti

        $files = Storage::files('/documenti');
        Log::info("inizio: ");
        foreach ($files as $file) {
            Log::info("file: {$file}");

            $filename = pathinfo($file, PATHINFO_FILENAME);
            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $parts = explode('_', $filename);

            if (count($parts) < 2) {
                continue;
            }

            $cognome = trim($parts[0]);
            $nome = trim($parts[1]);
            $user = DB::connection('mysql_old')->table('employees')
                ->whereRaw('UPPER(cognome) = ?', [mb_strtoupper($cognome)])
                ->whereRaw('UPPER(nome) = ?', [mb_strtoupper($nome)])
                ->first();


            if (!$user) {
                Log::warning("Utente non trovato: {$cognome} {$nome}");
                continue;
            }

            $newFilename = $user->matricola . '_' . basename($file);

            //$newFilename = $user->matricola . '_' . $cognome.'_'.$nome.' Idonieta 2025.'.$extension;

            Storage::move(
                $file,
                dirname($file) . '/' . $newFilename
            );

            dump("Rinominato: {$newFilename}");
        }

        dd();

        // 1. Evitiamo che lo script vada in timeout se ci sono molti PDF o molte chiamate a Gemini
        set_time_limit(300); // Imposta il limite a 5 minuti

        // 2. Definiamo il percorso assoluto della cartella usando public_path()
        // Aggiungiamo '*.pdf' alla fine per dire a PHP di cercare SOLO i file con estensione .pdf
        $searchPattern = public_path('file/Commesse/*.pdf');

        // 3. Recuperiamo la lista di tutti i percorsi dei file PDF
        $files = glob($searchPattern);

        // Se non ci sono file o la cartella è vuota
        if (empty($files)) {
            Log::warning("Nessun file PDF trovato nella cartella: " . public_path('file/Commesse/'));
            return response()->json(['messaggio' => 'Nessun file trovato da elaborare.'], 200);
        }

        $risultati = [];
        $documentReaderService = new DocumentReaderService();

        // 4. Ciclo foreach per analizzare ogni singolo file trovato
        foreach ($files as $absoluteFilePath) {

            // Estraiamo solo il nome del file (es. "fattura_scansione.pdf") per i log e la risposta
            $nomeFile = basename($absoluteFilePath);

            Log::info("Inizio elaborazione file commessa: " . $nomeFile);

            try {
                // 5. Chiamata al tuo servizio (Spatie se nativo, Gemini se immagine/scansione)
                $numeroDocumento = $documentReaderService->estraiNumeroDocumento($absoluteFilePath);

                if ($numeroDocumento) {
                    Log::info("File $nomeFile elaborato con successo. Numero trovato: " . $numeroDocumento);

                    $risultati[] = [
                        'file' => $nomeFile,
                        'stato' => 'Successo',
                        'numero' => $numeroDocumento
                    ];

                    // [FACOLEATIVO] Qui puoi inserire la tua logica per salvare il dato nel DB
                    // es. Commessa::where('file_name', $nomeFile)->update(['numero' => $numeroDocumento]);

                } else {
                    Log::warning("Impossibile trovare il numero nel file commessa: " . $nomeFile);
                    $risultati[] = [
                        'file' => $nomeFile,
                        'stato' => 'Non Trovato',
                        'numero' => null
                    ];
                }

            } catch (Exception $e) {
                Log::error("Errore critico durante l'elaborazione del file $nomeFile: " . $e->getMessage());
                $risultati[] = [
                    'file' => $nomeFile,
                    'stato' => 'Errore',
                    'errore' => $e->getMessage()
                ];
            }
        }




        Log::channel('stderr')->info($risultati);


dd();
        Log::channel('stderr')->info('Entro');
        // 1. Definisci il percorso relativo all'interno dello storage (es. 'app/cartella/file.xlsx')
        $nomeFileOriginale = 'fede.XLSX';

        // Verifichiamo prima se il file esiste davvero nello storage per evitare errori
        if (!Storage::disk('local')->exists($nomeFileOriginale)) {
            Log::channel('stderr')->info('File originale non trovato nello storage. ');
            //return response()->json(['error' => 'File originale non trovato nello storage.'], 404);
        }

        // 2. Recupera il percorso fisico (assoluto) del file sul server
        $filePath = Storage::disk('local')->path($nomeFileOriginale);

        // 3. Avvia l'elaborazione e scarica il nuovo file Excel generato con i due fogli
        $nomeNuovoFile = 'Cavi_Elaborati_' . date('Y-m-d_H-i-s') . '.xlsx';
        Log::channel('stderr')->info('Avvio');

        return Excel::download(new CaviElaborazioneExport($filePath), $nomeNuovoFile);

        dd();
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('user');
        $roleBy = $request->get('role');
        $statoBy = $request->get('stato');


        if (empty($sortByName)) {
            $sortByName = 'full_name';
            $orderBy = 'asc';
        }

        $users = DB::table('users')
            ->Where(function ($query) use ($userBy) {
                if ($userBy)
                    $query->Where('full_name', 'LIKE', '%' . $userBy . '%');
            })
            ->Where(function ($query) use ($roleBy) {
                if ($roleBy)
                    $query->Where('role', '=', $roleBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('stato', $statoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);


        return response()->json($users);

    }

    public function getWeek($date)
    {
        $datee = Carbon::createFromFormat('Y-m-d',$date);
        $date = $datee->copy()->firstOfMonth()->startOfDay();
        $eom = $date->copy()->endOfMonth()->startOfDay();
        $giorniMese = $date->month($date->month)->daysInMonth;

        $dates = [];

        for($i = 1; $date->lte($eom); $i++){
            // Log::channel('stderr')->info('i: '.$i);
            if($i == 1)
                $start = Carbon::createFromFormat('Y-m-d',$datee->year.'-'.$date->month.'-01');
            else
                $start = clone $date->startOfWeek(Carbon::MONDAY);

            $end = clone $date->endOfWeek(Carbon::SUNDAY);
            if($end->month != $datee->month)
                $end = Carbon::createFromFormat('Y-m-d','2026-'.$datee->month.'-'.$giorniMese);

            if($end->day < 5){
                $date->addDays(1 );
                $end = clone $date->endOfWeek(Carbon::SUNDAY);
            }

            if($i == 5){
                $dates[$i-1]['end'] = $end->toDateString();
            }else{
                $dates[$i]['start'] = $start->toDateString();
                $dates[$i]['end'] = $end->toDateString();
            }

            $date->addDays(1 );
        }

        return $dates;
    }

    private function validateBase64(string $base64data, array $allowedMimeTypes)
    {
        // strip out data URI scheme information (see RFC 2397)
        if (str_contains($base64data, ';base64')) {
            list(, $base64data) = explode(';', $base64data);
            list(, $base64data) = explode(',', $base64data);
        }

        // strict mode filters for non-base64 alphabet characters
        if (base64_decode($base64data, true) === false) {
            return false;
        }

        // decoding and then re-encoding should not change the data
        if (base64_encode(base64_decode($base64data)) !== $base64data) {
            return false;
        }

        $fileBinaryData = base64_decode($base64data);

        // temporarily store the decoded data on the filesystem to be able to use it later on
        $tmpFileName = tempnam(sys_get_temp_dir(), 'medialibrary');
        file_put_contents($tmpFileName, $fileBinaryData);

        $tmpFileObject = new \Illuminate\Http\File($tmpFileName);

        // guard against invalid mime types
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        // if there are no allowed mime types, then any type should be ok
        if (empty($allowedMimeTypes)) {
            return $tmpFileObject;
        }

        // Check the mime types
        $validation = Validator::make(
            ['file' => $tmpFileObject],
            ['file' => 'mimes:' . implode(',', $allowedMimeTypes)]
        );

        if($validation->fails()) {
            return false;
        }

        return $tmpFileObject;
    }


    private function checkUser($ssh, $username)
    {
        try {
            $ssh->read('itl-s-wlc-cl#');
            $ssh->write("show aaa local guest_user " . $username . "\n");
            $result = $ssh->read('itl-s-wlc-cl#');
            $arr_user = explode(" : ", $result);
            // verifico che l'account dell'utente sia presente.
            if (!empty($arr_user[1])) {
                $ssh->write("exit\n");
                return true;
            } else
                return false;

        } catch (\Exception $e) {
            return false;
        }
    }

    public function store(Request $request)
    {

        $request->validate([
            'nome' => 'required',
            'cognome' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required',
            'stato' => 'required',
            'lingua' => 'required',
        ]);

        $input = $request->except(['password', 'nome', 'cognome', 'id']);
        $input['password'] = Hash::make($request->password);
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'] . ' ' . $input['cognome'];
        $input['username'] = (empty($request->username) ? $request->email : $request->username);


        $user = User::create($input);

        if ($user->sesso == 'm')
            $user->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
        else
            $user->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
        $user->password_changed_at = Date('Y-m-d H:i:s');
        $user->save();
        $user->assignRole($request->input('role'));

        LogActivity::addToLog('New User ', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'info', 'new');


        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Creato',
                'color' => 'success'
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nome' => 'required',
            'cognome' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'role' => 'required',
            'stato' => 'required',
            'lingua' => 'required',
        ]);

        //Log::channel('stderr')->info($request->nome);
        //activity()->log('Look mum, I logged something');

        $input = $request->except(['nome', 'cognome']);
        $input['userId'] = $request->id;
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'] . ' ' . $input['cognome'];
        $input['username'] = (empty($request->username) ? $request->email : $request->username);

        User::find($id)->update($input);
        $user = User::find($id);
        LogActivity::addToLog('Edit User', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'success', 'edit');

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Modificato',
                'color' => 'success'
            ]
        );
    }

    public function view($id)
    {

        $user = User::find($id);

        return response()->json(['user' => $user]);
    }

    public function userOnLine($id)
    {

        return response()->json(['online' => (Cache::has('user-is-online-' . $id) ? true : false)]);

    }

    public function usersOnline()
    {
        $userOnline = 0;
        foreach (User::all()->pluck('id')->toArray() as $id) {
            if ((Cache::has('user-is-online-' . $id)))
                $userOnline++;
        }

        return response()->json(['online' => $userOnline]);
    }

    public function totalUsers(Request $request)
    {

        $users = DB::table('users')->Where(function ($query) use ($request) {
            if (!empty($request->activity))
                $query->Where('stato', '=', 1);
        })->count();

        return response()->json(['totalUsers' => $users]);
    }

    public function reset_password(Request $request, $id)
    {
        $user = User::find($id);
        Log::channel('stderr')->info(Hash::make($request->password));
        $user->password = Hash::make($request->password);
        $user->password_changed_at = null;
        $user->save();

        LogActivity::addToLog('Reset Password User', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'success', 'edit');

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Password-Resettata',
                'color' => 'success'
            ]
        );
    }

    public function delete($id)
    {

        $user = User::find($id);
        $user->stato = 0;
        $user->save();

        LogActivity::addToLog('Deleted User ', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'error', 'deleted');


        return response()->json(
            [
                'success' => true,
                'message' => 'User Created'
            ]
        );
    }

    public function activities($id)
    {
        return response()->json(LogActivity::where('user_id', $id)->orderBy('id', 'DESC')->take(10)->get());
    }

    public function getUsersPermission(Request $request)
    {
        $users = [];
        if ($request->permission)
            $users = User::permission($request->permission)->get();


        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

    public function getUsers()
    {
        $users = User::select('*')
            ->where('stato', 1)
            ->orderBy('nome')->get();

        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

    public function impersona($id)
    {
        Log::channel('stderr')->info('ok');
        $new_user = User::find($id);
        Auth::user()->impersonate($new_user);
        Log::channel('stderr')->info(Auth::user());
        dd();

        $tokenResult = $user->createToken('Personal Access Token', ['*'], \Illuminate\Support\Carbon::now()->addDay(1));
        $token = $tokenResult->plainTextToken;
        $perissions = [];
        if ($user->role != 'super admin') {
            $perissions_objs = $user->getAllPermissions();
            $perissions[] = ['action' => 'view', 'subject' => 'Dashboards'];

            foreach ($perissions_objs as $obj) {
                //Log::channel('stderr')->info($obj);
                $tmp = explode(".", $obj->name);
                $perm_name = ($tmp[count($tmp) - 1] == 'admin' ? 'manage' : $tmp[count($tmp) - 1]);
                unset($tmp[count($tmp) - 1]);
                $subject = array_search(implode('.', $tmp), Permission::$module_names);
                $perissions[] = ['action' => $perm_name, 'subject' => $subject];
            }
        } else
            $perissions[] = ['action' => 'manage', 'subject' => 'all'];

        LogActivity::addToLog('Impersona', ['avatar' => $user->avatar, 'full_name' => $user->full_name, 'ip' => $_SERVER['REMOTE_ADDR']], 'info', 'login');

        $days_between = ceil(abs(strtotime(date('Y-m-d H:i:s')) - strtotime($user->password_changed_at)) / 86400);
        return response()->json([
            'userAbilityRules' => $perissions,
            'userData' => [
                'id' => $user->id,
                'user_original' => Auth::id(),
                'fullName' => $user->full_name,
                'username' => $user->nome,
                'avatar' => $user->avatar,
                'email' => $user->email,
                'role' => ucwords($user->role),
                'passwordExpired' => ($days_between >= 60 ? true : false),
            ],
            'accessToken' => $token,
            'token_type' => 'Bearer',
            'expiredToken' => Carbon::parse(
                $tokenResult->accessToken->expires_at
            )->toDateTimeString()
        ]);
    }

    /**
     * @throws Exception
     */
    public function mensa()
    {
        stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
        $day_of_week = date("N") - 5;
        $primo_giorno = strtotime("-$day_of_week days");
        $path = 'https://app.metallurgicabresciana.it/turni/mb/menza/api/get.php?';
        //$path.= 'time='.date('Y-m-d',$primo_giorno);
        $path .= 'time=' . date('Y-m-d');
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);

        $spreadsheet = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();


        $activeWorksheet->setCellValue('A1', 'Pientanza');
        $activeWorksheet->setCellValue('B1', 'Matricola');
        $activeWorksheet->setCellValue('C1', 'Dipendente');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Costo');
        $i = 2;
        foreach ((array)$result->list as $row) {
            $activeWorksheet->setCellValue('A' . $i, $row[0]);
            $activeWorksheet->setCellValue('B' . $i, $row[1]);
            $activeWorksheet->setCellValue('C' . $i, $row[2]);
            $activeWorksheet->setCellValue('D' . $i, $row[3]);
            $activeWorksheet->setCellValue('E' . $i, $row[4]);
            $i++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save('C:/xampp/htdocs/tmp//' . date('Y_m_d') . '.xlsx');

        $file = public_path('file/' . date('Y_m_d') . '.xlsx');

        $users = Utility::users_notify(['mensa_week']);

        Mail::send('emails/email_mensa', [], function ($message) use ($file, $primo_giorno, $users) {
            $message
                ->to('gregorio.grande@stl.tech')
                ->subject('Mensa Del ' . date('Y-m-d'));

            $message->attach($file);
        });
        File::delete($file);
    }

}
