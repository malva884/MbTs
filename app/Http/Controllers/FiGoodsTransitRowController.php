<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FiGoodsTransitRowController extends Controller
{
    public function list(Request $request, $id)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $materialeBy = $request->get('materiale');
        $lavorazioneBy = $request->get('lavorazione');
        $dataBy = $request->get('data');
        $clienti = json_decode($request->get('clienti') ?? '[]') ?: [];


        if (empty($sortByName)) {
            $sortByName = 'date_row';
            $orderBy = 'desc';
        }
        $objs = DB::table('fi_goods_transit_rows')
            ->where('head', $id)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('material', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($clienti) {
                if (count($clienti))
                    $query->WhereIn('code_client', $clienti);
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy)
                    $query->Where('type', $lavorazioneBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy){
                    $dataBy = explode(' to ',$dataBy);
                    if(count($dataBy) == 2)
                        $query->whereBetween('date_row', $dataBy);
                    else
                        $query->Where('date_row', $dataBy[0]);

                }
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);

        return response()->json($objs);
    }

    public function report(Request $request, $id)
    {
        $materialeBy = $request->get('materiale');
        $lavorazioneBy = $request->get('lavorazione');
        $dataBy = $request->get('data');
        $clienti = json_decode($request->get('clienti') ?? '[]') ?: [];

        $baseQuery = function () use ($id, $materialeBy, $lavorazioneBy, $dataBy, $clienti) {
            return DB::table('fi_goods_transit_rows')
                ->where('head', $id)
                ->Where(function ($query) use ($materialeBy) {
                    if ($materialeBy)
                        $query->Where('material', 'LIKE', '%' . $materialeBy . '%');
                })
                ->Where(function ($query) use ($clienti) {
                    if (count($clienti))
                        $query->WhereIn('code_client', $clienti);
                })
                ->Where(function ($query) use ($lavorazioneBy) {
                    if ($lavorazioneBy)
                        $query->Where('type', $lavorazioneBy);
                })
                ->Where(function ($query) use ($dataBy) {
                    if ($dataBy) {
                        $dataBy = explode(' to ', $dataBy);
                        if (count($dataBy) == 2)
                            $query->whereBetween('date_row', $dataBy);
                        else
                            $query->Where('date_row', $dataBy[0]);
                    }
                });
        };

        $ottico = $baseQuery()->where('type', 5420)
            ->select(DB::raw('SUM(qty_value) as totale'), DB::raw('SUM(qty_fkm) as fkm'), DB::raw('SUM(delivered_qty) as ckm'))
            ->first();

        $rame = $baseQuery()->where('type', 5441)
            ->select(DB::raw('SUM(qty_value) as totale'), DB::raw('SUM(delivered_qty) as ckm'))
            ->first();

        $clientiReport = $baseQuery()
            ->select('code_client', 'client',
                DB::raw('SUM(qty_value) as totale'),
                DB::raw('SUM(CASE WHEN type = 5420 THEN qty_value ELSE 0 END) as ottico'),
                DB::raw('SUM(CASE WHEN type = 5441 THEN qty_value ELSE 0 END) as rame'))
            ->groupBy('code_client', 'client')
            ->orderBy('totale', 'desc')
            ->get();

        return response()->json([
            'ottico' => $ottico,
            'rame' => $rame,
            'clienti' => $clientiReport,
        ]);
    }

    public function export(Request $request, $id)
    {
        $materialeBy = $request->get('materiale');
        $lavorazioneBy = $request->get('lavorazione');
        $dataBy = $request->get('data');
        $clienti = json_decode($request->get('clienti') ?? '[]') ?: [];

        $objs = DB::table('fi_goods_transit_rows')
            ->where('head', $id)
            ->Where(function ($query) use ($materialeBy) {
                if ($materialeBy)
                    $query->Where('material', 'LIKE', '%' . $materialeBy . '%');
            })
            ->Where(function ($query) use ($clienti) {
                if (count($clienti))
                    $query->WhereIn('code_client', $clienti);
            })
            ->Where(function ($query) use ($lavorazioneBy) {
                if ($lavorazioneBy)
                    $query->Where('type', $lavorazioneBy);
            })
            ->Where(function ($query) use ($dataBy) {
                if ($dataBy) {
                    $dataBy = explode(' to ', $dataBy);
                    if (count($dataBy) == 2)
                        $query->whereBetween('date_row', $dataBy);
                    else
                        $query->Where('date_row', $dataBy[0]);
                }
            })
            ->orderBy('date_row', 'desc')
            ->get();

        $head = DB::table('fi_goods_transit_heads')->where('id', $id)->first();
        $period = $head ? $head->anno . '-' . str_pad($head->mese, 2, '0', STR_PAD_LEFT) : $id;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Merce in Viaggio ' . $period);

        $headers = [
            'Data',
            'Codice Cliente',
            'Cliente',
            'Item',
            'Materiale',
            'Descrizione',
            'Tipo Cavo',
            'Commessa',
            'Codice Destinatario',
            'Destinatario',
            'Unit',
            'Quantità',
            'Valore Costo',
            'Numero Fibre',
            'Quantità Spedita',
            'Quantità Fkm',
            'Prezzo Km',
            'Costo al Km',
            'Prezzo Standard',
            'Ordine',
            'Profitto Netto',
            'Profitto %',
            'Cambio',
            'Cap',
            'Città',
            'Documento',
            'Distanza Km',
        ];

        foreach ($headers as $index => $header) {
            $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
        }

        $rowIndex = 2;
        foreach ($objs as $obj) {
            $tipologia = '';
            if ($obj->type == 5420) $tipologia = 'Ottico';
            elseif ($obj->type == 5441) $tipologia = 'Rame';

            $sheet->setCellValueByColumnAndRow(1, $rowIndex, $obj->date_row);
            $sheet->setCellValueByColumnAndRow(2, $rowIndex, $obj->code_client);
            $sheet->setCellValueByColumnAndRow(3, $rowIndex, $obj->client);
            $sheet->setCellValueByColumnAndRow(4, $rowIndex, $obj->item);
            $sheet->setCellValueByColumnAndRow(5, $rowIndex, $obj->material);
            $sheet->setCellValueByColumnAndRow(6, $rowIndex, $obj->description);
            $sheet->setCellValueByColumnAndRow(7, $rowIndex, $tipologia);
            $sheet->setCellValueByColumnAndRow(8, $rowIndex, $obj->commessa);
            $sheet->setCellValueByColumnAndRow(9, $rowIndex, $obj->code_recipient);
            $sheet->setCellValueByColumnAndRow(10, $rowIndex, $obj->recipient);
            $sheet->setCellValueByColumnAndRow(11, $rowIndex, $obj->unit);
            $sheet->setCellValueByColumnAndRow(12, $rowIndex, $obj->qty_value);
            $sheet->setCellValueByColumnAndRow(13, $rowIndex, $obj->cost_value);
            $sheet->setCellValueByColumnAndRow(14, $rowIndex, $obj->fiber_counter);
            $sheet->setCellValueByColumnAndRow(15, $rowIndex, $obj->delivered_qty);
            $sheet->setCellValueByColumnAndRow(16, $rowIndex, $obj->qty_fkm);
            $sheet->setCellValueByColumnAndRow(17, $rowIndex, $obj->price_km);
            $sheet->setCellValueByColumnAndRow(18, $rowIndex, $obj->cost_km);
            $sheet->setCellValueByColumnAndRow(19, $rowIndex, $obj->std_price);
            $sheet->setCellValueByColumnAndRow(20, $rowIndex, $obj->order);
            $sheet->setCellValueByColumnAndRow(21, $rowIndex, $obj->net_profit);
            $sheet->setCellValueByColumnAndRow(22, $rowIndex, $obj->profit_perc);
            $sheet->setCellValueByColumnAndRow(23, $rowIndex, $obj->exchange_rate);
            $sheet->setCellValueByColumnAndRow(24, $rowIndex, $obj->postal_code);
            $sheet->setCellValueByColumnAndRow(25, $rowIndex, $obj->city);
            $sheet->setCellValueByColumnAndRow(26, $rowIndex, $obj->document);
            $sheet->setCellValueByColumnAndRow(27, $rowIndex, $obj->km_distance);
            $rowIndex++;
        }

        foreach (range(1, count($headers)) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setAutoSize(true);
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0E0E0'],
            ],
        ];
        $sheet->getStyleByColumnAndRow(1, 1, count($headers), 1)->applyFromArray($headerStyle);

        $filePath = storage_path('app/merce_in_viaggio_export_' . date('Ymd_His') . '.xlsx');
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }
}
