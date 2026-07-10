<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\TenantAttributeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Impersonate;
    use HasRoles;


    protected $guard_name = 'api';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id',
        'full_name',
        'username',
        'company_id',
        'nome',
        'cognome',
        'sesso',
        'mobile',
        'interno',
        'lingua',
        'stato',
        'avatar',
        'img_signature',
        'email',
        'role',
        'password',
        '_deleted',
        'workflow',
        'password_changed_at',
        'google_token',
        'matricola'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Verifica se l'utente sta impersonando qualcuno
     */
    public function isImpersonating()
    {
        $token = $this->currentAccessToken();
        if (!$token) {
            return false;
        }

        return \Illuminate\Support\Facades\Cache::has('impersonation_' . $token->token);
    }

    /**
     * Ottiene i dati dell'impersonazione
     */
    public function getImpersonationData()
    {
        $token = $this->currentAccessToken();
        if (!$token) {
            return null;
        }

        return \Illuminate\Support\Facades\Cache::get('impersonation_' . $token->token);
    }

    /**
     * Verifica se l'utente può essere impersonato
     */
    public function canBeImpersonated()
    {
        // Gli utenti con ruolo admin non possono essere impersonati
        return !$this->hasRole('admin');
    }
}
