<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WfDocument extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['id','model','model_id','nome_file','tipologia','id_file_drive','riferimento','model_head_id','userOpenFile'];

    static function addDocument($model, $model_id, $riferimento, $nome_file, $tipologia, $id_path_file, $model_head_id)
    {

        $document = new WfDocument();
        $document->model = $model;
        $document->model_id = $model_id;
        $document->model_head_id = $model_head_id;
        $document->riferimento = $riferimento;
        $document->nome_file = $nome_file;
        $document->tipologia = $tipologia;
        $document->id_file_drive = $id_path_file;
        $document->save();
    }
}
