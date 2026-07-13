<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\LogActivity;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserLogActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\QueryBuilder;

class PermissionController extends Controller
{
    public function __construct()
    {
        //$this->middleware('can:permission list', ['only' => ['index', 'show']]);
        //$this->middleware('can:permission create', ['only' => ['create', 'store']]);
        //$this->middleware('can:permission edit', ['only' => ['edit', 'update']]);
        //$this->middleware('can:permission delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $query = Permission::query();
        
        // Search filter
        if ($request->has('q') && !empty($request->q)) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }
        
        // Module filter
        if ($request->has('module') && !empty($request->module)) {
            $query->where('name', 'like', $request->module . '.%');
        }
        
        // Permission type filter
        if ($request->has('permissionType') && !empty($request->permissionType)) {
            $query->where('name', 'like', '%.' . $request->permissionType);
        }
        
        $permissions = QueryBuilder::for($query)
            ->allowedFields(['id', 'name', 'created_at'])
            ->allowedSorts('name', 'created_at')
            ->paginate($request->get('itemsPerPage', 10));


        foreach ($permissions as $key => $permission) {

            $objs = DB::table('model_has_permissions')->select('users.id as user_id', 'users.full_name')
                ->join('users', 'users.id', 'model_has_permissions.model_id')
                ->where('permission_id', $permission->id)
                ->get();

            $user = [];
            foreach ($objs as $obj) {
                $user[] = 'manager';//$obj->full_name;
            }
            $permissions[$key]['assigned_to'] = $user;


        }

        return response()->json($permissions);

    }

    public function list_tab(Request $request, $id)
    {

        $user = User::find($id);
        $tab = [];
        $adminPermissions = Permission::where('name','LIKE',"%admin%")->pluck('name')->toArray();
        $permissions = Permission::all()->pluck('name')->toArray();
        $admins = [];
        $missingPermissions = [];
        
        foreach (Permission::$module_names as $key => $module_name) {
            $permissionName = $module_name . '.admin';
            if (Permission::where('name', $permissionName)->exists() && $user->hasDirectPermission($permissionName)){
                $admins[$module_name] = true;
            } elseif (!Permission::where('name', $permissionName)->exists()) {
                $missingPermissions[] = $permissionName;
            }
        }

        foreach (Permission::$module_names as $key => $module_name) {
            if (empty($tab[$module_name])) {
                $tab[$module_name]['module'] = $module_name;
                $tab[$module_name]['name'] = $key;
            }

            foreach (Permission::$permission_names as $permission) {
                $result = null;
                $permissionName = $module_name . '.' . $permission;
                if (in_array($permissionName, $permissions)) {
                    if (!$user->hasDirectPermission($permissionName) && empty($admins[$module_name]) )
                        $result = false;
                    else
                        $result = true;
                }
                $tab[$module_name][$permission] = $result;
            }
        }

        return response()->json([
            'userPermissions' => array_values($tab),
            'missingPermissions' => array_unique($missingPermissions)
        ]);

    }

    public function store(Request $request)
    {
        $permissionName = $request->name;
        if (!empty($permissionName)) {
            $permission = Permission::all()->where('name', '=', $permissionName)->first();
            if (empty($permission->id))
                Permission::create(['name' => $permissionName, 'guard_name' => 'api']);
        }

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::findOrFail($id);
        $permissionName = $request->name;

        if (!empty($permissionName)) {
            $existing = Permission::where('name', $permissionName)->where('id', '!=', $id)->first();
            if (empty($existing)) {
                $permission->name = $permissionName;
                $permission->save();
            }
        }

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function delete($id)
    {

        $obj = Permission::findOrFail($id);
        $obj->delete();

        return response()->json(
            [
                'success' => true,
            ]
        );

    }

    public function set_user(Request $request, $id)
    {
        $user = User::find($id);
        $user->revokePermissionTo($user->permissions);
        $new_permissions = [];

        foreach ($request->all() as $key => $permissions) {
            $module = $permissions['module'];
            if (!$permissions['admin'])
                foreach ($permissions as $permission => $value) {
                    if ($permission != 'module' && $permission != 'name' && $value)
                        $new_permissions[] = $module . '.' . $permission;
                }
            else
                $new_permissions[] = $module . '.admin';
        }
        $user->syncPermissions($new_permissions);

        LogActivity::addToLog('Edit Permissions User', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'success', 'edit');
        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Permessi-Salvati',
                'color' => 'success'
            ]
        );
    }

    public function userPermissions()
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json([]);
        }

        $perissions = [];
        if($user->role != 'super admin'){
            $perissions_objs = $user->getAllPermissions();
            $perissions[] =  ['action' => 'view', 'subject' => 'Dashboards'];

            foreach ($perissions_objs as $obj){
                $tmp = explode(".",$obj->name);
                $perm_name = ($tmp[count($tmp)-1] == 'admin' ? 'manage' : $tmp[count($tmp)-1]);
                unset($tmp[count($tmp)-1]);
                $subject = array_search(implode('.',$tmp),Permission::$module_names);
                $perissions[] = ['action' => $perm_name, 'subject' =>$subject];
            }
        }else
            $perissions[] = ['action' => 'manage', 'subject' =>'all'];

        return response()->json($perissions);
    }

    public function getModuleOptions()
    {
        $modules = [];
        foreach (Permission::$module_names as $key => $module_name) {
            $modules[] = [
                'title' => $key,
                'value' => $module_name,
            ];
        }

        return response()->json($modules);
    }

    public function getPermissionTypeOptions()
    {
        $types = [];
        foreach (Permission::$permission_names as $permission_name) {
            $types[] = [
                'title' => ucfirst($permission_name),
                'value' => $permission_name,
            ];
        }

        return response()->json($types);
    }

    public function createMissingPermissions()
    {
        $created = [];
        $existing = Permission::all()->pluck('name')->toArray();
        
        foreach (Permission::$module_names as $module_name) {
            foreach (Permission::$permission_names as $permission_name) {
                $permissionName = $module_name . '.' . $permission_name;
                if (!in_array($permissionName, $existing)) {
                    Permission::create([
                        'name' => $permissionName,
                        'guard_name' => 'api'
                    ]);
                    $created[] = $permissionName;
                }
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Created ' . count($created) . ' missing permissions',
            'created' => $created
        ]);
    }

    public function groupPermissionsUsers()
    {
        $objs = DB::table('model_has_permissions')->select('permissions.name as permission', 'users.id as user_id', 'users.full_name')
            ->join('permissions', 'permissions.id', 'model_has_permissions.permission_id')
            ->join('users', 'users.id', 'model_has_permissions.model_id')
            ->get();

        $result = [];
        foreach ($objs as $obj) {
            $result[$obj->permission][] = $obj->full_name;
        }

        return response()->json([
            'data' => $result
        ]);
    }

    public function permissionsOverview(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $modules = Permission::$module_names;
        $permissionTypes = Permission::$permission_names;
        $result = [];


        foreach ($modules as $moduleKey => $moduleName) {
            $result[$moduleKey] = [
                'module' => $moduleKey,
                'module_name' => $moduleName,
                'permissions' => []
            ];

            foreach ($permissionTypes as $permissionType) {
                $permissionName = $moduleName . '.' . $permissionType;
                $permission = Permission::where('name', $permissionName)->first();

                $users = [];
                $hasAdmin = false;

                // Check if permission exists
                if ($permission) {
                    // Get users with direct permission (only active users)
                    $directUsers = DB::table('model_has_permissions')
                        ->select('users.id', 'users.full_name', 'users.username')
                        ->join('users', 'users.id', 'model_has_permissions.model_id')
                        ->where('permission_id', $permission->id)
                        ->where('model_type', 'App\\Models\\User')
                        ->where('users.stato', 1)
                        ->get();

                    foreach ($directUsers as $user) {
                        $users[$user->id] = [
                            'id' => $user->id,
                            'full_name' => $user->full_name,
                            'username' => $user->username,
                            'source' => 'direct'
                        ];
                    }
                }

                // Check if any user has admin permission for this module
                $adminPermissionName = $moduleName . '.admin';
                $adminPermission = Permission::where('name', $adminPermissionName)->first();

                if ($adminPermission) {
                    $adminUsers = DB::table('model_has_permissions')
                        ->select('users.id', 'users.full_name', 'users.username')
                        ->join('users', 'users.id', 'model_has_permissions.model_id')
                        ->where('permission_id', $adminPermission->id)
                        ->where('model_type', 'App\\Models\\User')
                        ->where('users.stato', 1)
                        ->get();

                    foreach ($adminUsers as $user) {
                        if (!isset($users[$user->id])) {
                            $users[$user->id] = [
                                'id' => $user->id,
                                'full_name' => $user->full_name,
                                'username' => $user->username,
                                'source' => 'admin'
                            ];
                        } else {
                            $users[$user->id]['source'] = 'admin';
                        }
                    }
                    $hasAdmin = count($adminUsers) > 0;
                }

                $result[$moduleKey]['permissions'][$permissionType] = [
                    'name' => $permissionName,
                    'exists' => $permission !== null,
                    'users' => array_values($users),
                    'has_admin' => $hasAdmin,
                    'user_count' => count($users)
                ];
            }
        }

        return response()->json([
            'data' => array_values($result)
        ]);
    }
}
