<?php

namespace App\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Revolution\Google\Sheets\Facades\Sheets;


class MagazzinoCalcoloFinale implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sheets = null;
    public $material_class = [];

    private $head = null;

    /**
     * Create a new job instance.
     */
    public function __construct($headId)
    {
        $this->head = $headId;


    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Sheets::spreadsheet('1jq7Tkk9t0_FNrpcqU7fsDBZJkV4z3ACEhZPSjkN7bBY');
        Sheets::addSheet($this->head.' Details');
        Sheets::addSheet($this->head.' Summary');



    }
}
