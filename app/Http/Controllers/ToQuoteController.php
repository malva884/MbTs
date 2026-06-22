<?php

namespace App\Http\Controllers;

use App\Models\ToQuote;
use App\Models\ToQuoteCable;
use App\Models\ToQuoteCableStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LogActivity;
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
            $sortByName = 'numero';
            $orderBy = 'asc';
        }

         $objs = ToQuote::select('to_quotes.*','to_clients.ragione_sociale','to_quote_cables.codice','to_quote_cables.metri','to_quote_cables.created_at as data_creazione_cavo')
            ->leftJoin('to_quote_cables','to_quotes.id','to_quote_cables.preventivo_id')
            ->join('to_clients', 'to_clients.id', '=', 'to_quotes.cliente_id')
            ->Where(function ($query) use ($cavoBy) {
                if ($cavoBy)
                    $query->Where('to_quote_cables.codice', 'LIKE' ,'%'.$cavoBy.'%')->orWhere('to_quote_cables.descrizione', 'LIKE' ,'%'.$cavoBy.'%');
            })
            ->Where(function ($query) use ($clienteBy) {
                if ($clienteBy)
                    $query->Where('cliente_id', $clienteBy);
            })
            ->Where(function ($query) use ($numeroBy) {
                if ($numeroBy)
                    $query->Where('numero', 'LIKE', '%' . $numeroBy . '%');
            })
			->Where(function ($query) use ($annoBy) {
                if ($annoBy)
                    $query->WhereYear('data_preventivo', $annoBy);
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
				$obj_struct->costo = (!empty($row->material->costo) ? $row->material->costo : 0.00);
				$obj_struct->costo_centro = (!empty($row->center->costo) ? $row->center->costo : 100.00);
				if($obj_struct->peso)
                    $obj_struct->costo_materia_prima = round(($obj_struct->peso * $obj_struct->costo) / 1000, 4);
				if(!empty($obj_struct->centro) && !empty($obj_struct->elementi) && !empty($obj_struct->ordinata)){
					$obj_struct->costo_lavorazione = round((($obj_struct->costo_centro / $obj_struct->ordinata) * $obj_struct->elementi) / 1000,4);
				}
				
                
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

        $quote = DB::table('to_quotes')
		->leftJoin('users','to_quotes.user','users.id')
		->leftJoin('to_clients','to_quotes.cliente_id','to_clients.id')
		->select('to_quotes.*','to_clients.ragione_sociale','users.full_name')
		->where('to_quotes.id', '=', $id)->first();
        $cables = DB::table('to_quote_cables')->select('to_quote_cables.*')
            //->join('to_reels','to_reels.id','to_quote_cables.bobina_id')
            ->where('preventivo_id', '=', $id)
            ->orderBy('posizione', 'asc')
            ->get();

        $sheet->setCellValue('A1', 'PREVENTIVO N° '.$quote->numero);
        $sheet->setCellValue('E1', $quote->ragione_sociale );
        $sheet->setCellValue('H1', $quote->full_name );
        $sheet->setCellValue('B2', date('d-m-Y',strtotime($quote->created_at)) );
        $sheet->setCellValue('E2', $quote->rdo );
        $sheet->setCellValue('B3', $quote->cu );
        $sheet->setCellValue('E3', date('d-m-Y',strtotime($quote->data_rdo)));
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
            $sheet->setCellValue('U' . $i, round($cable->m3_totale*$cable->bobina_numero,2));
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

        $objs = ToQuoteCable::select('to_quote_cables.*','to_quotes.numero','to_quotes.data_preventivo','to_clients.ragione_sociale','to_quotes.cu')
            ->join('to_quotes','to_quotes.id','to_quote_cables.preventivo_id')
            ->join('to_clients','to_clients.id','to_quotes.cliente_id')
            ->whereIn('to_quote_cables.id',$ids)
            ->get();

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
		$spreadsheet = $reader->load("file/stampa.xlsx");
        //$sheet = $spreadsheet->getActiveSheet();

        $result = [];
        $i = 1;
		$invalidCharacters = array('*', ':', '/', '\\', '?', '[', ']');
        foreach ($ids as $id){
			$tmp = $objs->where('id',$id)->first();
			$title = str_replace($invalidCharacters, '_', $tmp->codice);
            $clonedWorksheet = clone $spreadsheet->getSheetByName('T');
            $clonedWorksheet->setTitle($i.'_'.$title);
            $spreadsheet->addSheet($clonedWorksheet);
            $sheet = $spreadsheet->setActiveSheetIndex($i);

            //Log::channel('stderr')->info((array)$tmp);
           // $result[$id]['cavo'] = $tmp;
            //$result[$id]['preventivo'] = $tmp->numero;
            //$result[$id]['data_preventivo'] = $tmp->data_preventivo;
           //$result[$id]['struttura'] = ToQuoteCableStructure::where('cavo_id',$id)->orderby('posizione', 'asc')->get();


            $sheet->setCellValue('A1', 'PREVENTIVO N° '.$tmp->numero);
            $sheet->setCellValue('C1', $tmp->descrizione);
            $sheet->setCellValue('D1', 'NORME '.$tmp->norma);
            $sheet->setCellValue('H1', 'CLIENTE '.$tmp->ragione_sociale);
            $sheet->setCellValue('L1', 'NOME FILE '.$tmp->codice );

            //$struttura = ToQuoteCableStructure::where('cavo_id',$id)->orderby('posizione', 'asc')->get();
            $struttura = DB::table('to_quote_cable_structures')
                ->leftJoin('to_center_costs','to_quote_cable_structures.centro','to_center_costs.centro')
                ->leftJoin('to_materials','to_quote_cable_structures.materiale','to_materials.materiale')
                ->select('to_quote_cable_structures.*','to_center_costs.id as centro_check','to_materials.id as matariale_check', DB::raw('IIF(peso = 0.00, 0.22, peso) as  peso_mat'))
                ->where('cavo_id', $id)
                ->orderby('posizione', 'asc')
                ->get();

            $r = 3;
            foreach ( $struttura as $row) {
                $sheet->setCellValue('A'.$r, $row->centro);
                if(!$row->centro_check)
                    $sheet->getStyle('A'.$r)
                        ->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->setCellValue('B'.$r, $row->materiale);
                if(!$row->matariale_check)
                    $sheet->getStyle('B'.$r)
                        ->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED);
                $sheet->setCellValue('C'.$r, $row->descrizione);
                $sheet->setCellValue('D'.$r, $row->diametro);
                $sheet->setCellValue('E'.$r, $row->peso);
                $sheet->setCellValue('F'.$r, $row->costo);
                $sheet->setCellValue('G'.$r, $row->costo_materia_prima);
                $sheet->setCellValue('H'.$r, $row->costo_centro);
                $sheet->setCellValue('I'.$r, $row->ordinata);
                $sheet->setCellValue('J'.$r, $row->elementi);
                $sheet->setCellValue('K'.$r, $row->costo_lavorazione);
                $sheet->setCellValue('L'.$r, $row->nota);
                $sheet->setCellValue('M'.$r, $row->ore_macchina);
                $r++;
            }

            $sheet->getStyle('A3:M'.$r-1)
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A3:M'.$r-1)
                ->getFill()->getStartColor()->setARGB('81FFFF');


            $sheet->mergeCells('A'.$r.':M'.$r);
            $r++;
            $t = $r;
            $sheet->setCellValue('A'.$r, 'M');
            $sheet->setCellValue('B'.$r, $tmp->metri);
            $sheet->setCellValue('D'.$r, 'kg/km');
            $sheet->setCellValue('E'.$r, $tmp->peso_materie);
            $sheet->setCellValue('F'.$r, 'SOMMA M.P.');
            $sheet->setCellValue('G'.$r, $tmp->somma_materiali);
            $sheet->mergeCells('H'.$r.':J'.$r);
            $sheet->setCellValue('H'.$r, 'COSTO MANODOPERA');
            $sheet->setCellValue('K'.$r, $tmp->costo_manodopera);
            $sheet->setCellValue('L'.$r, 'TIPO BOBINA=');
            $sheet->setCellValue('M'.$r, $tmp->bobina);

            $r++;
            $sheet->setCellValue('C'.$r, 'VARIANTE RAME '.$tmp->variante_rame);
            $sheet->setCellValue('L'.$r, 'Netto Kg =');
            $sheet->setCellValue('M'.$r, $tmp->netto);

            $r++;
            $sheet->setCellValue('C'.$r, 'COSTO Cu ('.$tmp->cu.') = € '.round($tmp->variante_rame * $tmp->cu,4));
            $sheet->setCellValue('D'.$r, '% SCARTI');
            $sheet->setCellValue('E'.$r, round($tmp->scarto,0));
            $sheet->setCellValue('F'.$r, 'SCARTO');
            $sheet->setCellValue('G'.$r, $tmp->costo_scarto);
            $sheet->setCellValue('L'.$r, 'Lordo Kg =');
            $sheet->setCellValue('M'.$r, $tmp->lordo);

            $r++;
            $sheet->mergeCells('H'.$r.':J'.$r);
            $sheet->setCellValue('H'.$r, 'COSTO MATERIE PRIME');
            $sheet->setCellValue('K'.$r, $tmp->costo_materiali);
            $sheet->setCellValue('L'.$r, 'M3 =');
            $sheet->setCellValue('M'.$r, $tmp->m3);

            $r++;
            $sheet->setCellValue('C'.$r, 'COSTO TOTALE (BASE CU '.$tmp->cu.')'.$tmp->costo);
            $sheet->mergeCells('H'.$r.':J'.$r);
            $sheet->setCellValue('H'.$r, 'COSTO TOTALE');
            $sheet->setCellValue('K'.$r, $tmp->costo);
            $sheet->setCellValue('L'.$r, 'EURO =');
			$sheet->setCellValue('M'.$r, $tmp->totale_costo_bobine);
            //$sheet->setCellValue('M44', $tmp->m3);

            $sheet->getStyle('A'.$t.':M'.$r)
                ->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            
            $sheet->getStyle('A'.$t.':M'.$r)
                ->getFill()->getStartColor()->setARGB('A7FFA7');

            $sheet->getStyle('A3:M'.$r)
                ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);


            $sheet->getStyle("A1:M".$r)->getFont()->setSize(9);
            $i++;
        }
        $sheetIndex = $spreadsheet->getIndex(
            $spreadsheet->getSheetByName('T')
        );
        $spreadsheet->removeSheetByIndex($sheetIndex);
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
        $writer->save('stampa.xlsx');

        return  response()->download( public_path('stampa.xlsx'));
        //return response()->json($result);
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
        $obj->delete();
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
