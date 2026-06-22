<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SupplierExport implements FromCollection, WithHeadings
{
    private $ragioneSociale = null;
    private $codiceSap = null;
    private $categoria = null;

    public function __construct($ragioneSociale, $codiceSap, $categoria)
    {
        $this->ragioneSociale = $ragioneSociale;
        $this->codiceSap = $codiceSap;
        $this->categoria = $categoria;
    }

    public function collection()
    {
        $ragioneSociale = $this->ragioneSociale;
        $codiceSap = $this->codiceSap;
        $categoria = $this->categoria;

        // Gestisci valore "undefined" come vuoto
        if ($categoria === 'undefined' || $categoria === null) {
            $categoria = null;
        }

        $query = DB::connection('sqlsrv_fornitori')->table('suppliers')
            ->select('ragioneSociale', 'codiceSap', 'nazione', 'indirizzo', 'cap', 'citta', 'email', 'servizio', 'disattivo', 'qualificato', 'prezzo', 'critico', 'categoria', 'rating')
            ->where('disattivo', false);

        if ($ragioneSociale) {
            $query->Where('ragioneSociale', 'LIKE', '%' . $ragioneSociale . '%');
        }

        if ($codiceSap) {
            $query->Where('codiceSap', 'LIKE', '%' . $codiceSap . '%');
        }

        if ($categoria) {
            $query->Where('categoria', '=', $categoria);
        }

        $suppliers = $query->orderBy('ragioneSociale', 'asc')->get();

        return $suppliers;
    }

    public function headings(): array
    {
        return [
            'Ragione Sociale',
            'Codice SAP',
            'Nazione',
            'Indirizzo',
            'CAP',
            'Città',
            'Email',
            'Servizio',
            'Disattivo',
            'Qualificato',
            'Prezzo',
            'Critico',
            'Categoria',
            'Rating'
        ];
    }
}
