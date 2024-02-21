<?php
namespace App\Models;
use Spatie\Permission\Models\Permission as OriginalPermission;
class Permission extends OriginalPermission
{
    public $guard_name = 'api';

    protected $fillable = [
        'name',
        'guard_name',
        'updated_at',
        'created_at',
    ];

    static $module_names = [
        'Users'=>'user',
        'Visitors'=>'visitor',
        'Emploees'=>'emploees',
    ];

    static $permission_names = [
        'create','edit','read','list','import','sing','deleted'
    ];
}
