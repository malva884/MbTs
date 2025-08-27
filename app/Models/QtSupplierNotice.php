<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QtSupplierNotice extends Model
{
    use HasUuids;
    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'notice_suppliers';

    protected $fillable = [
        'id',
        'supplier_id',
        'titolo',
        'testo',
        'certificato_id',
        'conformita_id',
        'ritardo_id',
        'scadenza',
        'visualizata',
        'risposta',
        'user_id',
    ];

    static public function stored($request,$fornitore){

        $obj = new QtSupplierNotice();
        $obj->titolo = !empty($request->titolo) ? $request->titolo : $request['titolo'];
        $obj->testo = !empty($request->testo) ? $request->testo : $request['testo'];
        $obj->supplier_id = $fornitore;
        if(!empty($request['certificato_id']))
            $obj->certificato_id = $request['certificato_id'];
        if(!empty($request['conformita_id']))
            $obj->conformita_id = $request['conformita_id'];
        if(!empty($request['ritardo_id']))
            $obj->ritardo_id = $request['ritardo_id'];
        $obj->scadenza = !empty($request->scadenza) ? $request->scadenza : $request['scadenza'];
        $obj->user_id = Auth::id();
        $obj->save();



    }
}
