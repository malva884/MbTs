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
    private $path = null;
    private $fileWf = null;

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
        $files = Storage::disk('documenti')->allFiles();
        $this->path = public_path('file/Documenti/');

        foreach ($files as $file) {
            try {
                $tmp = explode('.', $file);
                $subs = explode(' ', $tmp[0]);
                $workflow = WfOrder::checkFlow($subs[0], 1);

                if(!empty($workflow->id)){
                    $check = WfDocument::where('riferimento', $subs[0])->where('nome_file', $file)->fist();
                    if(!empty($check->id)){
                        $documentId = GoogleDrive::add_file($workflow->folder_drive, $file, $this->path . $file, true);
                        WfDocument::addDocument($workflow::$modelName, $workflow->id, $subs[0], $file, 50, $documentId, $workflow->id);
                        if($documentId)
                            $this->deleted_file($this->path.$file);
                    }
                }

            } catch (\Exception $e) {
                Log::info($e);
                continue;
            }
        }
    }

    private function deleted_file($path)
    {
        @unlink($path);
    }
}
