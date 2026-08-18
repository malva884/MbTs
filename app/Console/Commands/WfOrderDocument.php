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

        // Ottieni il folder ID del disco documenti_drive dai settings
        $settingService = new \App\Services\SettingService();
        $documentiFolderId = $settingService->get('google_drive_documenti_folder_id');

        Log::info('Inizio elaborazione documenti commesse', [
            'documenti_folder_id' => $documentiFolderId,
        ]);

        // Verifica se la cartella Distinte esiste
        if (!$disk->exists('Distinte')) {
            Log::error('Cartella Distinte non trovata su Google Drive documenti_drive', [
                'documenti_folder_id' => $documentiFolderId,
                'folder_name' => 'Distinte',
            ]);
            return 1;
        }

        $files = $disk->files('Distinte');
        //$files = $disk->allFiles();

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

                        Log::info('Ricerca cartella Distinte', [
                            'documenti_folder_id' => $documentiFolderId,
                            'distinte_folder_id' => $distinteFolderId,
                            'file_name' => $fileName,
                        ]);

                        if ($distinteFolderId) {
                            // Cerca il file ID su Google Drive dentro la cartella Distinte
                            $fileId = GoogleDrive::search($distinteFolderId, 'documenti_drive', 'file', $fileName, false);

                            Log::info('Ricerca file su Google Drive', [
                                'distinte_folder_id' => $distinteFolderId,
                                'file_id' => $fileId,
                                'file_name' => $fileName,
                            ]);
                        } else {
                            $fileId = null;
                            Log::error('Cartella Distinte non trovata su Google Drive', [
                                'documenti_folder_id' => $documentiFolderId,
                                'folder_name' => 'Distinte',
                            ]);
                        }

                        if($fileId){
                            // Sposta il file direttamente su Google Drive
                            GoogleDrive::move($fileId, $workflow->folder_drive);

                            Log::info('File spostato su Google Drive', [
                                'file_id' => $fileId,
                                'workflow_folder_id' => $workflow->folder_drive,
                                'workflow_id' => $workflow->id,
                            ]);

                            // Registra il documento nel DB
                            WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $fileName, 50, $fileId, $workflow->id);
                        }else{
                            Log::error('File non trovato su Google Drive', [
                                'file_name' => $fileName,
                                'distinte_folder_id' => $distinteFolderId,
                            ]);
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
                Log::error('Errore durante elaborazione file', [
                    'file_name' => $fileName,
                    'documenti_folder_id' => $documentiFolderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }
    }
}
