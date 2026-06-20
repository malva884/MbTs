<?php

namespace App\Console\Commands;


use App\Jobs\MaterialiSync;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Models\WfUser;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class ImportOrders extends Command
{
    private $folderYearId = null;
    private $folderMonthId = null;
    private $folderCategoryId = null;
    private $path = null;
    private $fileWf = null;
    private $wfObj = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import_orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importazione Commesse Dal vecchio portale';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dates = ['2026-02-01','2026-02-04'];
        $workflows = DB::connection('mysql_old')->table('workflows')
            ->join('workflow_files','workflow_files.Workflow','workflows.id')
            ->join('workflow_categories','workflows.category','workflow_categories.id')
            ->select('workflows.*','path_folder_drive','path_drive as id_file_drive','nomeFile','category','workflow_categories.category as categoria')
            ->whereBetween('created_at',$dates)
            ->orderBy('type','asc')
            ->get();

        foreach ($workflows as $workflow){
            try {
                $category = DB::table('wf_categories')->where('categoria', '=', $workflow->categoria)->first();
                if(empty($category->id))   // TODO
                    continue;

                $this->wfObj = $this->checkFlow($workflow->commessa, 1, $category, null, true);


                switch ($workflow->type) {
                    case 1:
                        break;
                    case 3:

                        break;
                        }
                $revisione = null;



                if($workflow->category){
                    $tmp = explode('.', $workflow->nomeFile);
                    $subs = explode(' ', $tmp[0]);
                    preg_match_all('/[0-9]+/', $subs[1], $revisione);
                }





            } catch (\Exception $e) {
                Log::info('File: '.$workflow->commessa);
                Log::info($e);

                continue;
            }

        }
    }

    private function checkFlow($commessa, $tipologia, $category, $revisione = null)
    {
        $workflow = DB::table('wf_orders')->select('id','folder_drive','id_file_drive')
            ->where('commessa', '=', $commessa)
            ->where('tipologia', '=', $tipologia)
            ->Where(function ($query) use ($revisione) {
                if ($revisione)
                    $query->Where('revisione', $revisione);
            })
            ->first();

        return $workflow;
    }

    private function addWorkflow($categoria, $commessa, $commessa_sistema, $tipologia, $file, $path_file, $folder_id = null, $revisione = null, $visibile = true,  $id_commessa = null)
    {
        $workflow = new WfOrder();
        $workflow->creator = 5;
        $workflow->stato = 'Approved';
        $workflow->categoria_id = $categoria->id;
        $workflow->commessa_sistema = $commessa_sistema;
        $workflow->tipologia = $tipologia;
        $workflow->revisione = $revisione;
        $workflow->commessa = $commessa;

        if(!empty($folder_id))
            $workflow->folder_drive = $folder_id;
        elseif($this->folderCategoryId != $categoria->folder_drive){
            $this->folderCategoryId = $categoria->folder_drive;
            $this->folderYearId = GoogleDrive::add_folder([$categoria->folder_drive],date('Y'),null,true);
            $this->folderMonthId = GoogleDrive::add_folder([$this->folderYearId],date('M'),null,true);
        }

        if(empty($workflow->folder_drive))
            $workflow->folder_drive = GoogleDrive::add_folder([$this->folderMonthId],$commessa,null,true);
        $workflow->id_file_drive = GoogleDrive::add_file($workflow->folder_drive, $file, $path_file . $file, true);
        $workflow->visibile = $visibile;
        if($workflow->tipologia == 1)
            $workflow->id_commessa_padre = $id_commessa;
        $workflow->save();

        $model_head_id = $workflow->id;
        if($workflow->tipologia != 1)
            $model_head_id = $id_commessa;

        WfDocument::addDocument($workflow::$modelName, $workflow->id, $commessa, $file, $tipologia, $workflow->id_file_drive, $model_head_id);

        $typologyes = $workflow::$typologies;
        $notificationUserTypology = $workflow::$notificationUserTypology;
        if($notificationUserTypology[$tipologia])
            WfUser::notification($workflow::$modelName, $typologyes[$tipologia], '');

        return $workflow->id;
    }


}
