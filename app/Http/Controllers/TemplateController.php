<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Print\TemplateZpl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TemplateController extends Controller
{
    public function qtStrumenti(Request $request)
    {
        $content['id_instrument'] = $request->idInstrument;
        $content['serial_number']= $request->serialNumber;
        $content['inspector']= $request->inspector;
        $content['issuing_body']= $request->issuingBody;
        $content['frequency']= $request->frequency;
        $content['months']= $request->months;
        $content['from']= $request->from;
        $content['due']= $request->due;
        $content['Ip_Printer'] = '10.141.8.174';
		//$content['Ip_Printer'] = '10.141.3.111';

        TemplateZpl::printQuality($content);

        $message = 'Messaggi.Stampa-Effettuata';

        return response()->json(
            [
                'success' => true,
                'message' => $message,
                'color' => 'success',
                'obj' => null
            ]
        );
    }
}
