<?php

namespace App\Exports;

use App\Models\QtCheckerReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CheckerReportExport implements FromCollection, WithHeadings
{
    private $ordine = null;
    private $checker = null;
    private $periodo = null;
    private $lavorazione = null;
    public function __construct($checker,$ol,$periodo,$lavorazione)
    {

        $this->ordine = $ol;
        $this->checker = $checker;
        $this->periodo = $periodo;
        $this->lavorazione = $lavorazione;
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {

        $ol = $this->ordine;
        $checker = $this->checker;
        $periodo = $this->periodo;
        $lavorazione = $this->lavorazione;
        $type = QtCheckerReport::
            select('ol','users.full_name','coil','stage','num_fo','fo_try','km', 'not_conformity','lavorazione','tipo_cavo','note','date_create')
            ->join('users', 'users.id', 'qt_checker_reports.user')
            ->Where(function ($query) use ($checker) {
                if ($checker &&  $checker!= 'NULL'){
                    $query->Where('user', '=', $checker);
                }

            })
            ->Where(function ($query) use ($ol) {
                if ($ol != 'undefined')
                    $query->Where('ol', 'LIKE', '%' . $ol . '%');
            })
            ->Where(function ($query) use ($lavorazione) {
                if ($lavorazione)
                    $query->Where('lavorazione', '=', $lavorazione);
            })
            ->Where(function ($query) use ($periodo) {
                if ($periodo != 'null') {
                    $periodo = explode(' to ', $periodo);
                    if (count($periodo) == 2)
                        $query->whereBetween('date_create', [$periodo[0].' 00:00:00:000', $periodo[1].' 23:59:59:990']);
                    else
                        $query->whereDate('date_create', '=', $periodo[0]);
                }
            })->get();

        return $type;
    }

    public function headings(): array
    {
        return [
            'Ol','Checker', 'Bobina', 'Stage', 'Numero Fibre','Fibre Testate','Chilometri',
             'Non Conforme','Lavorazione','Descrizione Cavo','Nota','Data Inserimento'
        ];
    }
}
