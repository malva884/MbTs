<?php

namespace App\Console\Commands;

use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Models\WfUser;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WfOrderCopy extends Command
{
    private $folderYearId = null;
    private $folderMonthId = null;
    private $folderCategoryId = null;
    private $path = null;
    private $fileWf = null;

    private $category = null;
    private $wfObj = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:commesseCopy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'invio giornalienro assenza dipendenti';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $files = Storage::disk('commesse')->allFiles();
        $this->path = public_path('file/Commesse/');

        //foreach ($files as $file) {
            try {
                $objs = DB::connection('mysql_old')->table('workflows')
                    ->join('workflow_files','workflow_files.Workflow','workflows.id')
                    ->select('workflows.id','path_folder_drive','commessa','path_drive as id_file_drive','workflow_files.path_folder_drive','nomeFile','workflows.created_at','workflows.status','end_date')
                    ->whereYear('workflows.created_at','2026')
                    ->whereMonth('workflows.created_at','2')
                    ->where('workflows.type', '=', 1)
                    ->get();

                foreach ($objs as $obj){
                    $subs = explode(' ', $obj->commessa);
                    $cat = substr($subs[0], 0, 3);
                    $category = DB::table('wf_categories')->where('categoria', '=', $cat)->first();
                    $this->category = $category;

                    $workflow = WfOrder::checkFlow($obj->commessa,1);
                    if(!empty($workflow->id))
                        continue;

                    if(!$obj->path_folder_drive){
                        $obj->path_folder_drive = GoogleDrive::search($category->folder_drive,'google','dir',$obj->commessa,false);

                        if(empty($obj->path_folder_drive) || strlen($obj->path_folder_drive) > 33){
                            Log::info('string: '.strlen($obj->path_folder_drive));
                            dd();
                        }

                    }

                    if($obj->id_file_drive == null){
                        $obj->id_file_drive = GoogleDrive::search($obj->path_folder_drive,'google','file',$obj->commessa.'.pdf',false);

                        if(empty($obj->id_file_drive))
                            dd();
                    }

                    $workflow = WfOrder::addWorkflow($obj->commessa, 1, $this->category, $obj->commessa, null, $this->folderMonthId, null, true, $obj->path_folder_drive, 'Approved', $obj->created_at, $obj->end_date);
                    //$obj->id_file_drive = GoogleDrive::search($obj->path_folder_drive,'google','file',$obj->commessa.'.pdf',false);
                    $workflow->id_file_drive = $obj->id_file_drive;
                    if(!$obj->nomeFile)
                        $obj->nomeFile = $obj->commessa.'.pdf';
                    WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, $obj->nomeFile, 1, $obj->id_file_drive, $workflow->id);
                    if($obj->status == 4){
                        $logFile = GoogleDrive::search($obj->path_folder_drive,'google','file',$obj->commessa.'_'.$obj->end_date.'.pdf',false);
                        if($logFile){
                            $workflow->id_log_drive = $logFile;
                            WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, 'Log_'.$obj->commessa.'.pdf', 100, $workflow->id_log_drive , $workflow->id);
                        }
                    }
                    $workflow->save();
                }

            } catch (\Exception $e) {
                Log::info('Commessa: '.$obj->commessa);
                Log::info('Fine: '.$obj->end_date);
                Log::info('Id Cartella: '.$obj->path_folder_drive);
                Log::info('Log: '.$obj->commessa.'_'.$obj->end_date.'.pdf');
                Log::info($e);
            }
        //}
    }

    private function checkFlow($commessa, $tipologia, $revisione = null, $category = null, $oldWf = null)
    {
        $workflow = DB::table('wf_orders')->select('id','folder_drive','id_file_drive')
            ->where('commessa', '=', $commessa)
            ->where('tipologia', '=', $tipologia)
            ->Where(function ($query) use ($revisione) {
                if ($revisione)
                    $query->Where('revisione', $revisione);
            })
            ->first();
/*
        if (empty($workflow->id) && $oldWf){
            $workflow_exists = $this->checkFlowMySql($commessa);
            if (!empty($workflow_exists->id)){
                $workflow = $this->addOlfWorkflow($workflow_exists, $category, $commessa, $commessa, 1, $this->fileWf, $this->path);
            }
        }
*/
        return $workflow;
    }

    private function checkFlowMySql($commessa)
    {
        $workflow = DB::connection('mysql_old')->table('workflows')
            ->join('workflow_files','workflow_files.Workflow','workflows.id')
            ->select('workflows.id','path_folder_drive','commessa','path_drive as id_file_drive','workflow_files.path_folder_drive','nomeFile','workflows.created_at','workflows.status','end_date')
            ->where('workflows.commessa_name', '=', $commessa)
            ->where('workflows.type', '=', 1)
            ->first();

        return $workflow;
    }

    private function addOlfWorkflow($oldWf, $categoria, $commessa, $commessa_sistema, $tipologia, $file, $path_file, $folder_id = null, $revisione = null, $visibile = true,  $id_commessa = null)
    {
        $fileContents = GoogleDrive::download($oldWf->id_file_drive);
        Storage::disk('temp')->put($oldWf->nomeFile, $fileContents);
        if($oldWf->status == 4){
            Log::channel('stderr')->info((array)$oldWf);
            $logFile = GoogleDrive::search($oldWf->path_folder_drive,'google','file',$oldWf->commessa.'_'.$oldWf->end_date.'.pdf',false);
            $fileContents = GoogleDrive::download($logFile);
            Storage::disk('temp')->put('Log_'.$oldWf->commessa.'.pdf', $fileContents);
        }
        //$path =  public_path('file/temp/');
        $path =  storage_path('app/pdf/');
        $workflow = new WfOrder();
        $workflow->creator = 5;
        $workflow->stato = 'Approved';
        if($oldWf->status == 4)
            $workflow->data_approvazione = $oldWf->end_date;
        $workflow->categoria_id = $categoria->id;
        $workflow->commessa_sistema = $commessa_sistema;
        $workflow->tipologia = $tipologia;
        $workflow->revisione = $revisione;
        $workflow->commessa = $commessa;
        $workflow->created_at = $oldWf->created_at;

        $anno = date('Y',strtotime($oldWf->created_at));
        $mese = date('M',strtotime($oldWf->created_at));
        $folderYearId = GoogleDrive::add_folder([$categoria->folder_drive],$anno,null,true);
        $folderMonthId = GoogleDrive::add_folder([$folderYearId],$mese,null,true);

        $workflow->folder_drive = GoogleDrive::add_folder([$folderMonthId],$commessa,null,true);
        $workflow->id_file_drive = GoogleDrive::add_file($workflow->folder_drive, $oldWf->nomeFile, $path . $oldWf->nomeFile, true);
        $workflow->visibile = $visibile;
        $workflow->id_commessa_padre = $id_commessa;
        $workflow->save();

        WfDocument::addDocument($workflow::$modelName, $workflow->id, $commessa, $oldWf->nomeFile, $tipologia, $workflow->id_file_drive, $workflow->id);
        if(!empty($logFile))
        {
            $id_log = GoogleDrive::add_file($workflow->folder_drive, 'Log_'.$oldWf->commessa.'.pdf', $path . 'Log_'.$oldWf->commessa.'.pdf', true);
            WfDocument::addDocument($workflow::$modelName, $workflow->id, $commessa, 'Log_'.$oldWf->commessa.'.pdf', $tipologia, $id_log, $workflow->id);
            @unlink($path . 'Log_'.$oldWf->commessa.'.pdf');
        }


        @unlink($path.$oldWf->nomeFile);
        return $workflow;
    }

    private function addWorkflow($categoria, $commessa, $commessa_sistema, $tipologia, $file, $path_file, $folder_id = null, $revisione = null, $visibile = true,  $id_commessa = null)
    {
        $workflow = new WfOrder();
        $workflow->creator = 5;
        $workflow->stato = 'In-Approval';
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

    private function deleted_file($path)
    {
        @unlink($path);
    }
}
