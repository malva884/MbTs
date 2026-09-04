<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierRatingExport implements FromCollection, WithHeadings, WithStyles
{
    private $ragioneSociale = null;
    private $codiceSap = null;
    private $categoria = null;
    private $sortBy = null;
    private $orderBy = null;
    private $qualificato = null;

    public function __construct($ragioneSociale, $codiceSap, $categoria, $sortBy = 'ragioneSociale', $orderBy = 'asc', $qualificato = null)
    {
        $this->ragioneSociale = $ragioneSociale;
        $this->codiceSap = $codiceSap;
        $this->categoria = $categoria;
        $this->sortBy = $sortBy;
        $this->orderBy = $orderBy;
        $this->qualificato = $qualificato;
    }

    public function collection()
    {
        $ragioneSociale = $this->ragioneSociale;
        $codiceSap = $this->codiceSap;
        $categoria = $this->categoria;
        $sortBy = $this->sortBy;
        $orderBy = $this->orderBy;
        $qualificato = $this->qualificato;

        if ($categoria === 'undefined' || $categoria === null) {
            $categoria = null;
        }

        if ($qualificato === 'undefined' || $qualificato === null || $qualificato === '') {
            $qualificato = null;
        }

        $certificazioni = DB::connection('sqlsrv_fornitori')->table('certifications')->get();

        $query = DB::connection('sqlsrv_fornitori')->table('suppliers')
            ->where('disattivo', false)
            ->Where(function ($query) use ($ragioneSociale) {
                if ($ragioneSociale)
                    $query->Where('ragioneSociale', 'LIKE', '%' . $ragioneSociale . '%');
            })
            ->Where(function ($query) use ($categoria) {
                if ($categoria)
                    $query->Where('categoria', $categoria);
            })
            ->Where(function ($query) use ($codiceSap) {
                if ($codiceSap)
                    $query->Where('codiceSap', $codiceSap);
            })
            ->Where(function ($query) use ($qualificato) {
                if ($qualificato)
                    $query->Where('qualificato', true);
            });

        $query = $query->addSelect('suppliers.*');
        foreach($certificazioni as $certificazione){
            $query = $query->addSelect(DB::raw("(SELECT CASE
                                WHEN a.approvato = 1 THEN CONCAT(a.livello, ' ( ', a.scadenza,' )')
                                WHEN a.approvato = 0 THEN '0'
                                ELSE 'N'
                                END AS valutazione FROM supplier_certifications as a
                                left Join certifications as b on a.certificato_id = b.id
                                WHERE a.fornitore_id = suppliers.id
                                AND b.id = '".$certificazione->id."') as '".$certificazione->id."' "));
        }

        $suppliers = $query->orderBy($sortBy, $orderBy)->get();

        $formattedData = [];
        foreach ($suppliers as $supplier) {
            $row = [
                $supplier->ragioneSociale,
                $supplier->rating,
                $supplier->prezzo,
                $supplier->servizio,
                $supplier->critico ? 'Si' : 'No',
                $supplier->qualificato ? 'Si' : 'No',
                $supplier->categoria,
                $supplier->nazione,
            ];

            foreach ($certificazioni as $certificazione) {
                $certValue = $supplier->{$certificazione->id};
                $row[] = $certValue;
            }

            $formattedData[] = $row;
        }

        return collect($formattedData);
    }

    public function headings(): array
    {
        $certificazioni = DB::connection('sqlsrv_fornitori')->table('certifications')->get();

        $headings = [
            'Ragione Sociale',
            'Rating',
            'Prezzo',
            'Servizio',
            'Critico',
            'Qualificato',
            'Categoria',
            'Nazione',
        ];

        foreach ($certificazioni as $certificazione) {
            $headings[] = $certificazione->titolo;
        }

        return $headings;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
