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

        $permissions = QueryBuilder::for(Permission::class)
            ->allowedFields(['id', 'name','created_at'])
            //->allowedFilters(['full_name',AllowedFilter::exact('status'),AllowedFilter::exact('acl')])
            ->allowedSorts('name','created_at')
            ->paginate($request->get('itemsPerPage'));

        foreach ($permissions as $key => $permission){

            $objs = DB::table('model_has_permissions')->select('users.id as user_id', 'users.full_name')
                ->join('users','users.id','model_has_permissions.model_id')
                ->where('permission_id',$permission->id)
                ->get();

            $user = [];
            foreach ($objs as $obj){
                $user[] = 'manager';//$obj->full_name;
            }
            $permissions[$key]['assigned_to']= $user;



        }

        return response()->json($permissions);

    }

    public function list_tab(Request $request,$id){

        $user = User::find($id);
        $tab = [];
        $permissions = Permission::all()->pluck('name')->toArray();

        foreach (Permission::$module_names as $key => $module_name){
            if(empty($tab[$module_name])){
                $tab[$module_name]['module'] = $module_name;
                $tab[$module_name]['name'] = $key;
            }
            foreach (Permission::$permission_names as $permission){
                $result = null;
                if(in_array($module_name.'.'.$permission,$permissions)){
                    if(!$user->hasDirectPermission($module_name.'.'.$permission))
                        $result = false;
                    else
                        $result = true;
                }
                $tab[$module_name][$permission]=$result;
            }
        }
        //Log::channel('stderr')->info($tab);
        return response()->json([
            'userPermissions'=>array_values($tab)
        ]);

    }

    public function store(Request $request){
        $permissionName = $request->name;
        if(!empty($permissionName)){
            $permission = Permission::all()->where('name', '=', $permissionName)->first();
            if(empty($permission->id))
                Permission::create(['name' => $permissionName, 'guard_name' => 'api']);
        }

        return response()->json(
            [
                'success' => true,
            ]
        );
    }

    public function delete( $id)
    {

        $obj = Permission::findOrFail($id);
        $obj->delete();

        return response()->json(
            [
                'success' => true,
            ]
        );

    }

    public function set_user(Request $request, $id){
        $user = User::find($id);

        $user->revokePermissionTo($user->permissions);
        $new_permissions = [];

        foreach ($request->all() as $key => $permissions){
            $module = $permissions['module'];
            foreach ($permissions as $permission => $value){
                if($permission != 'module' && $permission != 'name' && $value)
                    $new_permissions[] = $module.'.'.$permission;
            }
        }

        $user->syncPermissions($new_permissions);

        LogActivity::addToLog('Edit Permissions User', ['avatar'=>$user->avatar,'full_name'=>$user->full_name],'success','edit');
        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Permessi-Salvati',
                'color' => 'success'
            ]
        );
    }

    public function userPermissions(){
        //$user = User::find($id);

        return auth()->check()?auth()->user()->jsPermissions():0;

    }

    public function groupPermissionsUsers(){
        $objs = DB::table('model_has_permissions')->select('permissions.name as permission', 'users.id as user_id', 'users.full_name')
            ->join('permissions','permissions.id','model_has_permissions.permission_id')
            ->join('users','users.id','model_has_permissions.model_id')
            ->get();

        $result = [];
        foreach ($objs as $obj){
            $result[$obj->permission][] = $obj->full_name;
        }

        return response()->json([
            'data'=>$result
        ]);
    }
}
