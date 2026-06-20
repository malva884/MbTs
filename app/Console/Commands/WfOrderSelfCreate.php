<?php

namespace App\Console\Commands;

use App\Jobs\FirmaCommesse;
use App\Models\WfDocument;
use App\Models\WfOrder;
use App\Models\WfUser;
use App\Services\GoogleDrive;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WfOrderSelfCreate extends Command
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
    protected $signature = 'app:commesse';

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

        foreach ($files as $file) {
            try {
                $this->fileWf = $file;
                $tmp = explode('.', $file);
                $subs = explode(' ', $tmp[0]);
                $cat = substr($subs[0], 0, 3);

                $commesse = explode("-", $subs[0]);
                $workflow = WfOrder::checkFlow($commesse[0], 1);

                if(empty( $this->category) || $cat != $this->category->categoria){
                    $category = DB::table('wf_categories')->where('categoria', '=', $cat)->first();
                    $this->category = $category;
                    if(empty($this->category->id))
                        continue;

                    if($this->folderCategoryId != $category->folder_drive){
                        $this->folderCategoryId = $category->folder_drive;
                        $this->folderYearId = GoogleDrive::add_folder([$category->folder_drive],date('Y'),null,true);
                        $this->folderMonthId = GoogleDrive::add_folder([$this->folderYearId],date('M'),null,true);
                    }
                }

                if(count($subs) == 1){
                    if(!empty($workflow->id))
                        continue;

                    $workflow = WfOrder::addWorkflow($subs[0], 1, $this->category, $subs[0], null, $this->folderMonthId, null, true);
                    $workflow->folder_drive = GoogleDrive::add_folder([$this->folderMonthId],$workflow->commessa,null,true);
                    $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $this->path . $file, true);
                    $workflow->id_file_drive = $id_file['id'];
                    $workflow->save();
                    WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $file, 1, $workflow->id_file_drive, $workflow->id);


                    if(count($commesse) == 2){
                        $commessa_t = $commesse[0];
                        $tmp = substr($commesse[0], 0, strlen($commesse[0])-strlen($commesse[1]));
                        $ultima_commessa = $tmp.$commesse[1];
                        while ($commessa_t <= $ultima_commessa) {
                            $workflow_t = WfOrder::addWorkflow($commessa_t, 1, $this->category, $subs[0], null, $this->folderMonthId, $workflow->id, false, $workflow->folder_drive);
                            $workflow_t->folder_drive = GoogleDrive::add_folder([$this->folderMonthId],$workflow_t->commessa,null,true);
                            $id_log = GoogleDrive::add_file($workflow_t->folder_drive, $file, $this->path . $file, true);
                            $workflow_t->id_file_drive = $id_log['id'];
                            $workflow_t->save();
                            WfDocument::addDocument($workflow::$modelName, $workflow_t->id, $commessa_t, $file, 1, $workflow_t->id_file_drive, $workflow_t->id);
                            $commessa_t++;
                        }
                    }
                    if($workflow->id_file_drive)
                        $this->deleted_file($this->path.$file);
                }else{
                    //Log::info('Revisione: '.$file);
                    $type = strtolower($subs[1]);
                    $type = substr($type, 0, 1);
                    Log::info('Tipologia: '.$type);
                    if ($type == 'r'){
                        $rev = '';

                        if(!empty($subs[2]))
                            $rev = $subs[2];
                        else
                            $rev = substr($subs[1], 1, 1);

                        //Log::info('Rev: '.$rev);

                        if(empty($workflow->id)){
                            $workflow_old = WfOrder::checkFlowOld($subs[0]);
                            if(!empty($workflow_old->id)){
                                $path =  storage_path('app/pdf/');
                                $workflow = WfOrder::addWorkflow($subs[0], 1, $this->category, $subs[0], null, $this->folderMonthId, null, true, null, 'Approved');
                                // passare Documenti
                                $fileContents = GoogleDrive::download($workflow_old->id_file_drive);
                                Storage::disk('temp')->put($workflow_old->nomeFile, $fileContents);
                                $workflow->folder_drive = GoogleDrive::add_folder([$this->folderMonthId],$workflow->commessa,null,true);
                                $id_file = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path . $workflow_old->nomeFile, true);
                                $workflow->id_file_drive = $id_file['id'];
                                WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, $workflow_old->nomeFile, 1, $workflow->id_file_drive, $workflow->id);
                                if($workflow_old->status == 4){
                                    $logFile = GoogleDrive::search($workflow_old->path_folder_drive,'google','file',$workflow_old->commessa.'_'.$workflow_old->end_date.'.pdf',false);
                                    if($logFile){
                                        $fileContents = GoogleDrive::download($logFile);
                                        Storage::disk('temp')->put('Log_'.$workflow_old->commessa.'.pdf', $fileContents);
                                        $id_file_c = GoogleDrive::add_file($workflow->folder_drive, $workflow_old->nomeFile, $path . 'Log_'.$workflow_old->commessa.'.pdf', false);
                                        $workflow->id_log_drive = $id_file_c['id'];
                                        WfDocument::addDocument($workflow::$modelName, $workflow->id, $workflow->commessa, 'Log_'.$workflow_old->commessa.'.pdf', 100, $workflow->id_log_drive , $workflow->id);
                                        @unlink($path . 'Log_'.$workflow_old->commessa.'.pdf');
                                    }
                                }
                                $workflow->save();
                            }
                        }

                        $workflow_rev = WfOrder::checkFlow($subs[0], 3, $rev);

                        if (!empty($workflow->id) && empty($workflow_rev->id)){
                            $workflow_rev = WfOrder::addWorkflow($subs[0], 3, $this->category, $subs[0], $rev, $this->folderMonthId, $workflow->id, true);
                            //$workflow_rev->folder_drive = GoogleDrive::add_folder([$this->folderMonthId],$workflow_rev->commessa,null,true);
                            $workflow_rev->folder_drive = $workflow->folder_drive;
                            $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $this->path . $file, true);
                            $workflow_rev->id_file_drive = $id_file['id'];
                            WfDocument::addDocument($workflow_rev::$modelName, $workflow_rev->id, $workflow_rev->commessa, $file, 3, $workflow_rev->id_file_drive , $workflow->id);
                            $workflow_rev->save();
                            if($workflow_rev->id_file_drive)
                                $this->deleted_file($this->path.$file);

                            $workflow->id = $workflow_rev->id;
                        }

                    }
                }

                if(!empty($workflow->id))
                    Dispatch(new FirmaCommesse($workflow->id));

            } catch (\Exception $e) {
                Log::info('File: '.$file);
                Log::info($e);

                continue;
            }
        }
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

        if (empty($workflow->id) && $oldWf){
            $workflow_exists = $this->checkFlowMySql($commessa);
            if (!empty($workflow_exists->id)){
                $workflow = $this->addOlfWorkflow($workflow_exists, $category, $commessa, $commessa, 1, $this->fileWf, $this->path);
            }
        }

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
        $id_file = GoogleDrive::add_file($workflow->folder_drive, $oldWf->nomeFile, $path . $oldWf->nomeFile, true);
        $workflow->id_file_drive = $id_file['id'];
        $workflow->visibile = $visibile;
        $workflow->id_commessa_padre = $id_commessa;
        $workflow->save();

        WfDocument::addDocument($workflow::$modelName, $workflow->id, $commessa, $oldWf->nomeFile, $tipologia, $workflow->id_file_drive, $workflow->id);
        if(!empty($logFile))
        {
            $id_log = GoogleDrive::add_file($workflow->folder_drive, 'Log_'.$oldWf->commessa.'.pdf', $path . 'Log_'.$oldWf->commessa.'.pdf', true);
            WfDocument::addDocument($workflow::$modelName, $workflow->id, $commessa, 'Log_'.$oldWf->commessa.'.pdf', $tipologia, $id_log['id'], $workflow->id);
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
        $id_file = GoogleDrive::add_file($workflow->folder_drive, $file, $path_file . $file, true);
        $workflow->id_file_drive = $id_file['id'];
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
