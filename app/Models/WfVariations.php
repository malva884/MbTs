<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WfVariations extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'wf_variations';

    protected $fillable = [
        'id', 'ol', 'stato', 'creator', 'testo', 'revisione', 'categoria_id',
        'data_approvazione', 'end_date', 'tipologia', 'visibile',
        'folder_drive', 'id_file_drive', 'id_log_drive',
    ];

    public static $modelName = 'WfVariations';

    public static $WfMode = 'standard';

    public static $roleIdApproved = ['Approvatore'];

    public static $typologieDocuments = ['Variazione' => 5, 'Log Approvazioni' => 100];

    /**
     * Categoria (wf_categories) associata alla variazione.
     */
    public function category()
    {
        return $this->belongsTo(WfCategory::class, 'categoria_id');
    }

    /**
     * Conteggio variazioni per lo storico badge (porting di get_user_workflow).
     *
     * @param  int  $user    id utente
     * @param  bool $approve utenti approvatori (true) o visualizzatori (false)
     * @param  int  $type    1 = da firmare, 2 = firmate, 3 = visualizzate/non visualizzate
     * @param  string|null $status
     */
    public static function get_user_workflow($user, $approve, $type, $status = null)
    {
        $count = 0;

        if ($approve) {
            $query = self::leftJoin('wf_user_approvals', function ($join) use ($user) {
                $join->on('wf_variations.id', '=', 'wf_user_approvals.model_id')
                    ->where('wf_user_approvals.model', '=', 'WfVariations')
                    ->where('wf_user_approvals.user_id', '=', $user);
            })
                ->where('wf_variations.stato', 'In-Approval')
                ->where('wf_variations.visibile', true)
                ->where(function ($q) use ($type) {
                    if ($type == 1)
                        $q->whereNull('wf_user_approvals.model_id');
                    else
                        $q->whereNotNull('wf_user_approvals.model_id');
                });

            $count = $query->distinct('wf_variations.id')->count('wf_variations.id');
        }
        else {
            // type=3 → visualizzate (esiste riga 'Viewed'), altrimenti → non visualizzate
            $count = self::where('wf_variations.stato', '!=', 'End')
                ->where('wf_variations.visibile', true)
                ->{$type == 3 ? 'whereIn' : 'whereNotIn'}('wf_variations.id', function ($sub) use ($user) {
                    $sub->select('model_id')
                        ->from('wf_user_approvals')
                        ->where('model', 'WfVariations')
                        ->where('user_id', $user)
                        ->where('approval_action', 'Viewed');
                })
                ->count();
        }

        return $count;
    }
}
