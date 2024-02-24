<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\LogActivity as LogActivityModel;
use Illuminate\Support\Facades\Request;

class LogActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject', 'url', 'html','color','method', 'ip', 'agent', 'user_id','created_at'
    ];

    public static function addToLog($subject,$param,$color,$style){
        switch ($style) {
            case "edit":
                $html = '<div class="d-flex align-center mt-3">
                  <div class="v-avatar v-theme--dark v-avatar--density-default v-avatar--variant-flat me-2" style="width: 34px; height: 34px;">
                  <div class="v-responsive v-img" aria-label="">
                  <div class="v-responsive__sizer" style="padding-bottom: 100%;"></div>
                  <img class="v-img__img v-img__img--cover" src="http://127.0.0.1:5173/resources/images/avatars/'.$param['avatar'].'" alt="" style=""></div>
                  <span class="v-avatar__underlay"></span></div><div>
                  <h6 class="text-sm font-weight-medium mb-n1"> '.$param['full_name'].'</h6>
                  </div></div>';
                self::stored($subject,$html,$color);
                break;
            case "login":
                $html = '<div class="d-flex align-center mt-3">
                  <h6 class="text-sm font-weight-medium mb-n1"> Ip: '.$param['ip'].'</h6></div>';
                self::stored($subject,$html,$color);
                break;
            case "edit_generic":
                $icon = 'tabler-file';
                if(!empty($param['icon']))
                    $icon = $param['icon'];
                $html = '<div class="d-flex justify-space-between align-center flex-wrap gap-2 mb-3">
                <span class="app-timeline-title">
                  '.$param['titolo'].'
                </span>
                <span class="app-timeline-meta"></span>
              </div>

              <p class="app-timeline-text mb-2">
                '.$param['descrizione'].'
              </p>
              <div class="d-flex align-center mt-2">
                <VIcon
                  color="error"
                  icon="'.$icon.'"
                  size="18"
                  class="me-2"
                />
                <h6 class="font-weight-medium text-sm">
                  '.$param['nome'].'
                </h6>
              </div>';
                self::stored($subject,$html,$color);
                break;
            default:
                echo "Your favorite color is green!";
        }
    }

    private static function stored($subject,$html,$color)
    {
        $log = [];
        $log['subject'] = $subject;
        $log['html'] = $html;
        $log['color'] = $color;
        $log['url'] = Request::fullUrl();
        $log['method'] = Request::method();
        $log['ip'] = Request::ip();
        $log['agent'] = Request::header('user-agent');
        $log['user_id'] = auth()->check() ? auth()->user()->id : 1;
        LogActivityModel::create($log);

    }


    public static function logActivityLists($id = null)
    {
        return LogActivityModel::all();
    }
}
