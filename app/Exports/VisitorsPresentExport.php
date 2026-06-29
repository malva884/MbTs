<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VisitorsPresentExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        $objs = DB::table('rp_register_activities')
            ->select('rp_register_logs.nome', 'rp_register_logs.azienda', 'rp_register_logs.email', 'rp_register_activities.data_azione')
            ->join('rp_register_logs', 'rp_register_logs.id', 'rp_register_activities.rp_register_id')
            ->where('rp_register_activities.presente', true)
            ->where('rp_register_activities.azione', 'Entrata')
            ->whereDate('rp_register_activities.data_azione', today())
            ->orderBy('rp_register_activities.data_azione', 'desc')
            ->get();

        return $objs;
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Azienda',
            'Email',
            'Data Ingresso',
        ];
    }
}
