<?php

namespace App\Exports;

use App\Models\QtConformita;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ConformitaExport implements FromCollection, WithHeadings
{
    private $ordine = null;
    private $materiale = null;
    private $difetto = null;
    private $linea = null;

    private $periodo = null;
    public function __construct($materiale,$ol,$difetto,$linea,$periodo)
    {
        $this->ordine = $ol;
        $this->materiale = $materiale;
        $this->difetto = $difetto;
        $this->linea = $linea;
        $this->periodo = $periodo;

    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $linea = $this->linea;
        $ol = $this->ordine;
        $materiale = $this->materiale;
        $difetto = $this->difetto;
        $periodo = $this->periodo;
        $type = QtConformita::
            select(DB::raw('CONCAT(anno, numero) AS id_conformita'),'ol','materiale','bobina','stage','machineries.nome','physical_l','optical_l',
            'defects.difetto','fibre','soluzione','qt_conformitas.stato'
                ,'note','diametro','num_fo','tipologia_fibra',
                'tipologia_difetto','operator','data_apertura','data_chiusura','time','users.full_name','motivazione_chiusura_text')
            ->join('users', 'users.id', 'qt_conformitas.user')
            ->join('machineries', 'machineries.id', 'qt_conformitas.macchina')
            ->join('defects', 'defects.id', 'qt_conformitas.difetto')
            ->Where(function ($query) use ($linea) {
                if ($linea)
                    $query->Where('machineries.id', '=', $linea);
            })
            ->Where(function ($query) use ($ol) {
                if ($ol)
                    $query->Where('ol', 'LIKE', '%' . $ol . '%');
            })
            ->Where(function ($query) use ($materiale) {
                if ($materiale)
                    $query->Where('materiale', 'LIKE', '%' . $materiale . '%');
            })
            ->Where(function ($query) use ($difetto) {
                if ($difetto)
                    $query->Where('defects.id', '=', $difetto);
            })
            ->Where(function ($query) use ($periodo) {
                if ($periodo) {
                    $periodo = explode(' to ', $periodo);
                    if (count($periodo) == 2)
                        $query->whereBetween('data_apertura', [$periodo[0].' 00:00:00', $periodo[1].' 23:59:59']);
                    else
                        $query->whereDate('data_apertura', '=', $periodo[0]);
                    //$query->where('data_apertura', '<=', $periodo[0].' 24:59:59:999');

                }

            })
            ->get();
        return $type;
    }

    public function headings(): array
    {
        return [
            'Id','Ol','Materiale','Bobina','Stage','Macchina','physical_l','optical_l',
            'Difetto','Fibre','Soluzione','Stato','Note','Diametro','Numero Fibre','Tipologia Fibra',
            'Tipologia Difetto','Operatore Buffering','Data Apertura','Data Chiusura','time','Utente','Motivazione Chiusura'
        ];
    }
}
