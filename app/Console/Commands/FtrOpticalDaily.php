<?php

namespace App\Console\Commands;

use App\Models\Utility;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;



class FtrOpticalDaily extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:ftr_optical_daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ftr Giornalienro Produzione Ottico';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ini_set('max_execution_time', -1);
        $periodo = [date('Y-m-01 00:00:00'),date('Y-m-d H:i:s')];
        $buf_count = DB::table('qt_checker_reports')
            ->select('ol','coil',DB::raw('COUNT(DISTINCT coil) as bob'))
            ->where('stage', 'BUF')
            ->whereBetween('date_create', $periodo)
            ->groupBy(['ol','coil'])
            ->get();

        $str_count = DB::table('qt_checker_reports')
            ->select('ol','coil',DB::raw('COUNT(DISTINCT coil) as bob'))
            ->where('stage', 'SZ')
            ->whereBetween('date_create', $periodo)
            ->groupBy(['ol','coil'])
            ->get();

        $jac_count = DB::table('qt_checker_reports')
            ->select('ol','coil',DB::raw('COUNT(DISTINCT coil) as bob'))
            ->whereIn('stage', ['FC','SF','PE'])
            ->whereBetween('date_create', $periodo)
            ->groupBy(['ol','coil'])
            ->get();

        $buf_t = $buf_count->count();
        $str_t = $str_count->count();
        $jac_t = $jac_count->count();

        $buf = $str = $jac = 0;

        $sheet_num_bobs = Sheets::spreadsheet('1Fx3y_JNkGrMwSeOTxfnADwpy8xTmUcNcM92M0zsHzaE')->sheet('TOT BOB X OL')->all();

        foreach ($sheet_num_bobs as $row) {
            if (!empty($row[6]) && is_numeric($row[6]))
                $buf += $row[6];

            if (!empty($row[10]) && is_numeric($row[10]))
                $str += $row[10];

            if (!empty($row[14]) && is_numeric($row[14]))
                $jac += $row[14];
        }


        $ftr = [];
        $bob_buf = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->where('stage', 'BUF')
            ->whereBetween('date_create', $periodo)
            ->first();

        $bob_sz = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->where('stage', 'SZ')
            ->whereBetween('date_create', $periodo)
            ->first();

        $bob_fc = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->where('stage', 'FC')
            ->whereBetween('date_create', $periodo)
            ->first();

        $bob_sf = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->where('stage', 'SF')
            ->whereBetween('date_create', $periodo)
            ->first();

        $bob_pe = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->where('stage', 'PE')
            ->whereBetween('date_create', $periodo)
            ->first();



        $nc_buf = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'BUF')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->where('stato',3)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_sz = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'SZ')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->where('stato',3)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_fc = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'FC')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->where('stato',3)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_sf = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'SF')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->where('stato',3)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_pe = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'PE')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->where('stato',3)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $jk_no_good = $nc_fc->count() + $nc_sf->count() + $nc_pe->count();
        $jk_bob = $bob_fc->bob + $bob_sf->bob + $bob_pe->bob;

        $info = [
            'BUFF' =>['prodotte'=>$buf,'nc'=>$nc_buf->count(),'tested'=>$bob_buf->bob,'color'=>'0b5394'],
            'SZ' =>['prodotte'=>$str,'nc'=>$nc_sz->count(),'tested'=>$bob_sz->bob,'color'=>'38761d'],
            'FC' =>['prodotte'=>$jac,'nc'=>$nc_fc->count(),'tested'=>$bob_fc->bob,'color'=>'333333'],
            'SF' =>['prodotte'=>'-','nc'=>$nc_sf->count(),'tested'=>$bob_sf->bob,'color'=>'333333'],
            'PE' =>['prodotte'=>'-','nc'=>$nc_pe->count(),'tested'=>$bob_pe->bob,'color'=>'333333'],
        ];


        $ftr['buf'] = ['bob_prod'=> $buf, 'nc'=>$nc_buf->count(), 'ftr' => ($buf_t > 0 ? round((($buf_t - $nc_buf->count()) / $buf_t ) * 100,2): 100)];
        $ftr['sz'] = ['bob_prod'=> $str, 'nc'=>$nc_sz->count(), 'ftr' =>($str_t > 0 ? round((($str_t - $nc_sz->count()) / $str_t ) * 100,2) : 100)];
        $ftr['jac'] = ['bob_prod'=> $jac, 'nc'=>$jk_no_good, 'ftr' => ($jac_t >0 ? round((($jac_t - $jk_no_good) / $jac_t ) * 100,2) : 100)];

        $option = "
                    {
          series: [{
          data: " . json_encode([$ftr['buf']['ftr'], $ftr['sz']['ftr'], $ftr['jac']['ftr']]) . "
        }],
		chart: {
          height: 350,
          type: 'bar',
          events: {
            click: function(chart, w, e) {
              // console.log(chart, w, e)
            }
          }
        },
        colors: ['#2E93fA', '#66DA26', '#546E7A', '#E91E63', '#FF9800'],
        plotOptions: {
          bar: {
            columnWidth: '45%',
            distributed: true,
			bar: {
				borderRadius: 10,
				dataLabels: {
				  position: 'top', // top, center, bottom
				},
			}
          }
        },
        dataLabels: {
          enabled: true,
          formatter: function (val) {
            return val + '%';
          },
          offsetY: -20,
          style: {
            fontSize: '12px',
          }
        },
        legend: {
          show: false
        },
        xaxis: {
          categories: " . json_encode(['BUF ('.$buf.' / '.$bob_buf->bob.' / '.$nc_buf->count().' / '.$buf_t.')',
                'SZ ('.$str.' / '.$bob_sz->bob.' / '.$nc_sz->count().' / '.$str_t.')',
                'JAK ('.$jac.' / '.$jk_bob.' / '.$jk_no_good.' / '.$jac_t.')',
            ]) . ",
          labels: {
            style: {
              colors: ['#2E93fA', '#66DA26', '#546E7A', '#E91E63', '#FF9800'],
              fontSize: '12px'
            }
          }
        }
        }";

        $post = 'https://quickchart.io/apex-charts/render?width=900&config=' . urlencode($option);
        $nameFile = 'Ftr_Stage_' . date(strtotime(date('Y-m-d H:i:s'))) . '.png';
        $streamContext = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ]);
        Storage::disk('ftp')->put("img_email_protale/" . $nameFile, file_get_contents($post,false, $streamContext));
        $file['Ftr_Stage'] = $nameFile;

        $users = Utility::users_notify(['pr_ftr_ottico_giornaliero']);

        Mail::send('emails/email_ftr_optical', compact('info','file'), function ($message) use ($users) {
            $message
                ->to('gregorio.grande@stl.tech')
                ->subject('Report FTR');
        });
    }
}
