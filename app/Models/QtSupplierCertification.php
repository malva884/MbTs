<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class QtSupplierCertification extends Model
{
    use HasUuids;

    protected $connection = 'sqlsrv_fornitori';
    protected $table = 'supplier_certifications';

    protected $fillable = [
        'id',
        'fornitore_id',
        'certificato_id',
        'questionario_id',
        'livello',
        'valutazione',
        'scadenza',
        'data_acquisizione',
        'file_id',
        'approvato',
        'user_id'
    ];

    public function supplier() {
        return $this->hasOne(QtSupplier::class, 'id', 'fornitore_id');
    }

    public function certificato() {
        return $this->hasOne(QtCertification::class, 'id', 'certificato_id');
    }
}
