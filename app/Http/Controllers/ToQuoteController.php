<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\ToQuote;
use App\Models\ToQuoteCable;
use App\Models\ToQuoteCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ToQuoteController extends Controller
{
    public function list(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $clienteBy = $request->get('cliente');
        $numeroBy = $request->get('numero');
        $dataBy = $request->get('data');
        $cavoBy = $request->get('cavo');
        $annoBy = $request->get('anno');

        if (empty($sortByName)) {
            $sortByName = 'created_at';
            $orderBy = 'desc';
        }

        $objs = ToQuote::select('to_quotes.*','to_clients.ragione_sociale','to_quote_cables.codice','to_quote_cables.metri','to_quote_cables.created_at as data_creazione_cavo')
            ->leftJoin('to_quote_cables','to_quotes.id','to_quote_cables.preventivo_id')
            ->join('to_clients', 'to_clients.id', '=', 'to_quotes.cliente_id')
            ->Where(function ($query) use ($cavoBy) {
                if ($cavoBy)
                    $query->Where('to_quote_cables.codice', 'LIKE' ,'%'.$cavoBy.'%');
            })
            ->Where(function ($query) use ($clienteBy) {
                if ($clienteBy)
                    $query->Where('cliente_id', $clienteBy);
            })
            ->Where(function ($query) use ($annoBy) {
                if ($annoBy)
                    $query->WhereYear('data_preventivo', $annoBy);
            })
            ->Where(function ($query) use ($numeroBy) {
                if ($numeroBy)
                    $query->Where('numero', 'LIKE', '%' . $numeroBy . '%');
            })
            ->orderBy($sortByName, $orderBy)
            ->paginate($request->itemsPerPage);

        //$strutturaCavo = DB::connection('mysql_old')->table('cable_structures')->orderby('position','asc')->take(1)->get();


        return response()->json($objs);
    }

    public function stored(Request $request)
    {
        $obj = new ToQuote();
        $obj->numero = $request->numero;
        $obj->user = Auth::id();
        $obj->rdo = $request->rdo;
        $obj->parametro = $request->parametro;
        $obj->cliente_id = $request->cliente_id;
        $obj->cu = $request->cu;
        $obj->data_rdo = $request->data_rdo;
        $obj->data_preventivo = (empty($request->data_preventivo) ? date('Y-m-d'):$request->data_preventivo);
        $obj->save();


        return response()->json(['id' => $obj->id]);
    }

    public function update(Request $request, $id)
    {
        $obj = ToQuote::find($id);
        $obj->numero = $request->numero;
        $obj->rdo = $request->rdo;
        $obj->parametro = $request->parametro;
        $obj->cliente_id = $request->cliente_id;
        $obj->cu = $request->cu;
        $obj->data_rdo = $request->data_rdo;
        $obj->nota = $request->nota;
        $obj->data_preventivo = (empty($request->data_preventivo) ? date('Y-m-d'):$request->data_preventivo);
        $obj->save();

        $message = 'Messaggi.Preventivo-Modificato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => $obj
            ]
        );
    }

    public function duplica(Request $request, $id)
    {
        $preventivo =  ToQuote::find($id);
        $nuovoPreventivo = $preventivo->replicate();
        $nuovoPreventivo->numero = $request->numero;
        $nuovoPreventivo->cliente_id = $request->cliente;
        $nuovoPreventivo->data_preventivo = date('Y-m-d');
        $nuovoPreventivo->save();

        $caviPreventivo = ToQuoteCable::where('preventivo_id', $id)->get();
        foreach ($caviPreventivo as $cavo){
            $nuovoCavo = $cavo->replicate();
            $nuovoCavo->preventivo_id = $nuovoPreventivo->id;
            $nuovoCavo->save();

            //$struttura = DB::table('to_quote_cable_structures')->where('cavo_id',$cavo->id)->get();
            $struttura =  ToQuoteCableStructure::where('cavo_id',$cavo->id)->get();
            foreach ($struttura as $row){
                $obj_struct = $row->replicate();
                $obj_struct->cavo_id = $nuovoCavo->id;
                $obj_struct->save();
            }
            $nuovoCavo->calcola_totali();
        }

        $message = 'Messaggi.Preventivo-Duplicato';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
            ]
        );
    }

    public function export_green_sheet($id)
    {

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        $spreadsheet = $reader->load("file/foglio_verde.xlsx");
        $sheet = $spreadsheet->getActiveSheet();

        $quote = DB::table('to_quotes')->select('*')->where('id', '=', $id)->first();
        $cables = DB::table('to_quote_cables')->select('to_quote_cables.*')
            //->join('to_reels','to_reels.id','to_quote_cables.bobina_id')
            ->where('preventivo_id', '=', $id)
            ->orderBy('posizione', 'asc')
            ->get();

        $sheet->setCellValue('A1', 'PREVENTIVO N° '.$quote->numero);
        $sheet->setCellValue('E1', $quote->cliente_id );
        $sheet->setCellValue('H1', $quote->user );
        $sheet->setCellValue('B2', $quote->user );
        $sheet->setCellValue('E2', $quote->rdo );
        $sheet->setCellValue('B3', $quote->cu );
        $sheet->setCellValue('E3', $quote->data_rdo );
        $sheet->setCellValue('F5', $quote->cu );
        $sheet->setCellValue('H5', $quote->parametro );

        $tot_netto = $tot_lordo = 0;
        $i = 6;
        foreach ($cables as $cable) {
            $sheet->setCellValue('B' . $i, $cable->metri);
            $sheet->setCellValue('C' . $i, $cable->descrizione);
            $sheet->setCellValue('E' . $i, $cable->variante_rame);
            $sheet->setCellValue('F' . $i, round($cable->costo,4));
            $sheet->setCellValue('N' . $i, $cable->diametro);
            $sheet->setCellValue('O' . $i, $cable->peso_materie);
            $sheet->setCellValue('P' . $i, $cable->codice);
            //$sheet->setCellValue('I' . $i, number_format($cable->cost * $cable->metri, 2, ",", ".") );


            $sheet->setCellValue('Q' . $i, $cable->bobina_numero);
            $sheet->setCellValue('R' . $i, $cable->bobina);
            $netto = (int)number_format(round($cable->peso_materie,0) * round($cable->metri,0), 0, ',', ',');
            $sheet->setCellValue('S' . $i, $netto);
            $lordo = (int)number_format(round($cable->peso,0) * 1 , 0, ',', ',');
            $sheet->setCellValue('T' . $i, $netto + $lordo);
            $sheet->setCellValue('U' . $i, $cable->m3_totale);
            $sheet->setCellValue('V' . $i, $cable->costo_bobina);

            $i++;
        }
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save('preventivo.xlsx');

        return  response()->download( public_path('preventivo.xlsx'));

    }

    public function get_cavi(Request $request)
    {

        $ids = json_decode($request->ids);
        if(!is_array($ids))
            $ids = [$ids];

        $objs = ToQuoteCable::select('to_quote_cables.*','to_quotes.numero','to_quotes.data_preventivo','to_clients.ragione_sociale')
            ->join('to_quotes','to_quotes.id','to_quote_cables.preventivo_id')
            ->join('to_clients','to_clients.id','to_quotes.cliente_id')
            ->whereIn('to_quote_cables.id',$ids)

            ->get();
        $result = [];
        foreach ($ids as $id){
            $tmp = $objs->where('id',$id)->first();
            //Log::channel('stderr')->info($tmp->numero);
            $result[$id]['cavo'] = $tmp;
            $result[$id]['preventivo'] = $tmp->numero;
            $result[$id]['data_preventivo'] = $tmp->data_preventivo;
            $result[$id]['struttura'] = ToQuoteCableStructure::where('cavo_id',$id)->orderby('posizione', 'asc')->get();
        }

        return response()->json($result);
    }

    public function view($id)
    {
        $obj = ToQuote::find($id);
        $obj->cliente_obj;


        return response()->json($obj);

    }

    public function deleted($id)
    {
        $obj = ToQuote::find($id);
        //$obj->delete();
        $message = 'Messaggi.Preventivo-Eliminato';
        $color = 'success';
        $success = true;

        $text ='
        <h6 class="font-weight-medium text-sm">Preventivo: '.$obj->numero.'</h6>';
        LogActivity::addToLog('Preventivo Eliminato', ['text'=>$text],'error','deleted');
        return response()->json(
            [
                'success' => $success,
                'message' => $message ,
                'color' => $color,
            ]
        );
    }
}
