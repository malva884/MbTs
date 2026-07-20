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

class WfOrderDocument extends Command
{
    private $folderYearId = null;
    private $folderMonthId = null;
    private $folderCategoryId = null;
    private $category = null;
    private $wfObj = null;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:orderDocument';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'archiviazione documenti commessa';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('documenti_drive');
        $files = $disk->files('Distinte');
        //$files = $disk->allFiles();

        // Ottieni il folder ID del disco documenti_drive dai settings
        $settingService = new \App\Services\SettingService();
        $documentiFolderId = $settingService->get('google_drive_documenti_folder_id');

        foreach ($files as $file) {
            try {
                $fileName = basename($file); // Rimuove il percorso, tiene solo il nome del file
                $tmp = explode('.', $fileName);
                $subs = explode(' ', $tmp[0]);
                $workflow = WfOrder::checkFlow($subs[0], 1);
                if(!empty($workflow->id)){
					$check = WfDocument::where('riferimento', $subs[0])->where('nome_file', $fileName)->first();
                    if(empty($check->id)){
                        // Cerca prima l'ID della cartella Distinte dentro Documenti
                        $distinteFolderId = GoogleDrive::search($documentiFolderId, 'documenti_drive', 'dir', 'Distinte', false);

                        if ($distinteFolderId) {
                            // Cerca il file ID su Google Drive dentro la cartella Distinte
                            $fileId = GoogleDrive::search($distinteFolderId, 'documenti_drive', 'file', $fileName, false);
                        } else {
                            $fileId = null;
                        }

                        if($fileId){
                            // Sposta il file direttamente su Google Drive
                            GoogleDrive::move($fileId, $workflow->folder_drive);

                            // Registra il documento nel DB
                            WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $fileName, 50, $fileId, $workflow->id);
                        }else{
                            Log::error('File non trovato su Google Drive: '.$fileName);
                        }
                    }else{
                        //Log::info('File Duplicato: '.$file);
                        // Sposta il file duplicato nella cartella duplicati
                        if (!$disk->exists('Distinte/duplicati')) {
                            $disk->makeDirectory('Distinte/duplicati');
                        }

                        $fileName = basename($file);
                        $duplicatePath = 'Distinte/duplicati/' . $fileName;

                        // Se esiste già un duplicato con lo stesso nome, aggiungi timestamp
                        if ($disk->exists($duplicatePath)) {
                            $pathInfo = pathinfo($fileName);
                            $newFileName = $pathInfo['filename'] . '_' . time() . '.' . ($pathInfo['extension'] ?? 'pdf');
                            $duplicatePath = 'Distinte/duplicati/' . $newFileName;
                        }

                        $disk->move($file, $duplicatePath);
					}
                }
				else{
						//Log::error('Commessa non trovata: '.$file);
					}

            } catch (\Exception $e) {
                Log::error($e);
                continue;
            }
        }
    }
}
