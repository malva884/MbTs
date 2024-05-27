<?php

namespace App\Console\Commands;

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

        $sheet_num_bobs = Sheets::spreadsheet('1Fx3y_JNkGrMwSeOTxfnADwpy8xTmUcNcM92M0zsHzaE')->sheet('TOT BOB X OL')->all();
        $buf = $str = $jac = 0;

        foreach ($sheet_num_bobs as $row) {
            if (!empty($row[6]) && is_numeric($row[6]))
                $buf += $row[6];

            if (!empty($row[6]) && is_numeric($row[10]))
                $str += $row[10];

            if (!empty($row[6]) && is_numeric($row[14]))
                $jac += $row[14];
        }

        $periodo = [date('Y-m-01 00:00:00'),date('Y-m-d H:i:s')];
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

        $bob_jac = DB::table('qt_checker_reports')
            ->select(DB::raw('COUNT(id) as bob'))
            ->whereIn('stage', ['FC','SF','PE'])
            ->whereBetween('date_create', $periodo)
            ->first();

        $nc_buf = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'BUF')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_sz = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->where('stage', 'SZ')
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $nc_jac = DB::table('qt_conformitas')
            ->select('ol','bobina',DB::raw('COUNT(DISTINCT bobina) as bob'))
            ->whereIn('stage', ['FC','SF','PE'])
            ->where('difetto','<>',20)
            ->where('motivazione_chiusura','<>',1)
            ->whereBetween('data_apertura', $periodo)
            ->groupBy(['ol','bobina'])
            ->get();

        $ftr['buf'] = ['bob_prod'=> $buf, 'nc'=>$nc_buf->count(), 'ftr' => round((($buf - $nc_buf->count()) / $buf ) * 100,2)];
        $ftr['sz'] = ['bob_prod'=> $str, 'nc'=>$nc_sz->count(), 'ftr' =>round((($str - $nc_sz->count()) / $str ) * 100,2)];
        $ftr['jac'] = ['bob_prod'=> $jac, 'nc'=>$nc_jac->count(), 'ftr' => round((($jac - $nc_jac->count()) / $jac ) * 100,2)];

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
          categories: " . json_encode(['BUF ('.$buf.' / '.$bob_buf->bob.' / '.$nc_buf->count().')',
                'SZ ('.$str.' / '.$bob_sz->bob.' / '.$nc_sz->count().')',
                'JAK ('.$jac.' / '.$bob_jac->bob.' / '.$nc_jac->count().')',
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
        Storage::disk('ftp')->put("img_email_protale/" . $nameFile, file_get_contents($post));
        $file['Ftr_Stage'] = $nameFile;
        $emails[] = ['antonio.adigrat@stl.tech','marcello.bergoli@stl.tech','gregorio.grande@stl.tech'];

        Mail::send('emails/email_ftr_optical', compact('file'), function ($message) use ($emails) {
            $message
                ->to($emails)
                ->subject('Report FTR');
        });
    }
}
