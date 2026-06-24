<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DipUser extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $connection = 'mysql_dipendenti';

    protected $table = 'users';

    protected $guarded = [];

    const ROLE_ADMIN = 'Admin';
    const ROLE_RISORSE_UMANE = 'RisorseUmane';
    const ROLE_GESTIONE_REME = 'GestioneReme';
    const ROLE_GESTIONE_OTTICO = 'GestioneOttico';
    const ROLE_GESTIONE = 'Gestione';
    const ROLE_GESTIONE_BACHECA = 'GestioneBacheca';
    const ROLE_EMPLOYEE = 'employee';

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'role' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(DipEmployee::class, 'employee_id', 'id');
    }
}
