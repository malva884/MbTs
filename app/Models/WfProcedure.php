<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfProcedure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','procedura','descrizione','revisione','revisione_anno','categoria_id','user_id',
        'stato','padre_id','sup','folder_drive','id_file_drive','id_log_drive','id_log_drive','tipologia','data_approvazione','folder_drive_padre','notification'];

    public static $modelName = 'WfProcedure';
    public static $WfMode = 'standard';
    public static $roleIdApproved = ['Approvatore'];
    public static $typologies = [1 => 'Procedure',];
    public static $notificationUserTypology = [1 => false];

    public static $tipologie = [1 => 'Procedura', 2 => 'Modulo', 3 => 'Istruzione', 4 => 'Altro'];

    public function Processo()
    {
        return $this->hasOne(WfCategory::class,'id', 'processo_id');
    }

    public function Categoria()
    {
        return $this->hasOne(WfCategory::class,'id', 'categoria_id');
    }
}
