<?php

namespace App\Exports;

use App\Models\QtConformita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConformitaExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $type = QtConformita::
            select(DB::raw('CONCAT(anno, numero) AS id_conformita'),'ol','materiale','bobina','stage','machineries.nome','physical_l','optical_l',
            'defects.difetto','fibre','soluzione','qt_conformitas.stato'
                ,'note','diametro','num_fo','tipologia_fibra',
                'tipologia_difetto','operator','data_apertura','data_chiusura','time','users.full_name')
            ->join('users', 'users.id', 'qt_conformitas.user')
            ->join('machineries', 'machineries.id', 'qt_conformitas.macchina')
            ->join('defects', 'defects.id', 'qt_conformitas.difetto')
            ->get();
        return $type;
    }

    public function headings(): array
    {
        return [
            'Id','Ol','Materiale','Bobina','Stage','Macchina','physical_l','optical_l',
            'Difetto','Fibre','Soluzione','Stato','Note','Diametro','Numero Fibre','Tipologia Fibra',
            'Tipologia Difetto','Operatore Buffering','Data Apertura','Data Chiusura','time','Utente'
        ];
    }
}
