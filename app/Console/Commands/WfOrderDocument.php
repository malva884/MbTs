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
        $files = $disk->allFiles();

        // Ottieni il folder ID del disco documenti_drive dai settings
        $settingService = new \App\Services\SettingService();
        $documentiFolderId = $settingService->get('google_drive_documenti_folder_id');

        foreach ($files as $file) {
            try {
                $tmp = explode('.', $file);
                $subs = explode(' ', $tmp[0]);
                $workflow = WfOrder::checkFlow($subs[0], 1);

                if(!empty($workflow->id)){
					$check = WfDocument::where('riferimento', $subs[0])->where('nome_file', $file)->first();
                    if(empty($check->id)){
                        // Cerca il file ID su Google Drive
                        $fileId = GoogleDrive::search($documentiFolderId, 'documenti_drive', 'file', $file, false);

                        if($fileId){
                            // Sposta il file direttamente su Google Drive
                            GoogleDrive::move($fileId, $workflow->folder_drive);

                            // Registra il documento nel DB
                            WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $file, 50, $fileId, $workflow->id);
                        }else{
                            Log::info('File non trovato su Google Drive: '.$file);
                        }
                    }else{
						//Log::info('File Già Presente: '.$file);
					}
                }
				else{
						Log::info('Commessa non trovata: '.$file);
					}

            } catch (\Exception $e) {
                Log::info($e);
                continue;
            }
        }
    }
}
