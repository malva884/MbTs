<?php

namespace App\Console\Commands;

use App\Models\PrMaterial;
use App\Models\PrMaterialMovement;
use App\Models\PrStockCategorie;
use App\Models\PrStockLotNotify;
use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class PrCheckQuantityStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pr_check_quantity_stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'controllo quantita resudue di materiale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $categorie = PrStockCategorie::all()->where('notifica',true);


        foreach ($categorie as $categoria){
            $materiali = DB::table('pr_materials')
                ->where('categorie','LIKE','%'.$categoria->tag.'%')
                ->pluck('materiale');
				
            $sheet = [];
            $result = DB::connection('sqlsrv_gp')
                ->table('AGG_GIACENZE')
                ->whereIn('cdProdotto',$materiali)
                ->where('Giacenza','<',$categoria->quantita)
                ->get();

            foreach ($result as $giacenza){
                $notifica = PrStockLotNotify::where('lotto',$giacenza->cdLotto);
                if(empty($notifica->id)){
                    $notifica = new PrStockLotNotify();
                    $notifica->materiale = $giacenza->cdProdotto;
                    $notifica->lotto = $giacenza->cdLotto;
                    $notifica->quantita = $giacenza->Giacenza;
                    $notifica->um = $giacenza->cdUM;

                    $sheet[] = [$notifica->lotto, $notifica->materiale, $notifica->quantita, $notifica->um];
                }elseif($giacenza->Giacenza < $notifica->quantita || $notifica->notifica){
                    $notifica->quantita = $giacenza->Giacenza;
                    $notifica->notifica = true;

                    $sheet[] = [$notifica->lotto, $notifica->materiale, $notifica->quantita, $notifica->um];
                }
                $notifica->save();
            }

			if(count($sheet)){
				$path_file = '/public/file/';
            $spreadsheet  = new Spreadsheet();
            $activeWorksheet = $spreadsheet->getActiveSheet();


            $activeWorksheet->setCellValue('A1', 'Materiale');
            $activeWorksheet->setCellValue('B1', 'Quantità');
            $activeWorksheet->setCellValue('C1', 'Um');
            $activeWorksheet->setCellValue('D1', 'Lotto');
            $i = 2;
            foreach ($sheet as $row){
                $activeWorksheet->setCellValue('A'.$i, $row[1]);
                $activeWorksheet->setCellValue('B'.$i, $row[2]);
                $activeWorksheet->setCellValue('C'.$i, $row[3]);
                $activeWorksheet->setCellValue('D'.$i, $row[0]);
                $i++;
            }

            $writer = new Xlsx($spreadsheet);
            $file_dir = dirname(__DIR__, 3).$path_file;
            if (!file_exists($file_dir)) {
                if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                    throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
                }
            }
            $file = $file_dir.'giacenza_'.date('Y_m_d_').'.xlsx';
            $writer->save($file);


            Mail::send('emails/email_giacenze', [], function ($message) use($file, $categoria){
                $message
                    //->to(explode(";",$categoria->utenti_notifica))
					->to(['gregorio.grande@stl.tech','antonio.guerreschi@stl.tech'])
                    ->subject('Giacenza '.$categoria->legenda.' Del '. date('Y-m-d'));

                $message->attach( $file);
            });
            File::delete($file);
			}
            

        }
    }
}
