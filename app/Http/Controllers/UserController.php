<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth', ['only' => ['create', 'store', 'edit', 'delete']]);
        // Alternativly
       // $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    public function index(Request $request){

        //$user = Auth::user();

       // $permissions = Permission::all();
        //foreach ($permissions as $permission){
            //$user->syncPermissions($permissions);

       // }

       // Permission::create(['name' => 'user.create', 'guard_name' => 'api']);
   /*     $image = QrCode::format('png')
            ->merge('https://w3adda.com/wp-content/uploads/2019/07/laravel.png', 0.3, true)
            ->size(200)
            ->errorCorrection('H')
            ->generate('Webappfix Qr Laravel Tutorial Example');
*/

        $users = QueryBuilder::for(User::class)
            ->allowedFields(['id', 'nome', 'cognome', 'role', 'stato','avatar','full_name'])
            ->allowedFilters(['full_name', AllowedFilter::exact('stato'), AllowedFilter::exact('role')])
            ->allowedSorts('nome', 'role', 'stato')
            ->paginate($request->get('perPage', 10));


        return response()->json($users);

    }

    public function store(Request $request){

        $request->validate([
            'nome' => 'required',
            'cognome' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required',
            'stato' => 'required',
            'lingua' => 'required',
        ]);

        $input = $request->except(['password','nome','cognome','id']);
        $input['password'] = Hash::make($request->password);
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'].' '.$input['cognome'];

        $user = User::create($input);

        if ($user->sesso == 'm')
            $user->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
        else
            $user->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
        $user->save();
        $user->assignRole($request->input('role'));

        LogActivity::addToLog('New User ', ['avatar'=>$user->avatar,'full_name'=>$user->full_name],'info','new');


        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Creato',
                'color' => 'success'
            ]
        );
    }

    public function update(Request $request, $id){
        $request->validate([
            'nome' => 'required',
            'cognome' => 'required',
            'email' => 'required|email|unique:users,email,' . $request->id,
            'role' => 'required',
            'stato' => 'required',
            'lingua' => 'required',
        ]);

        //Log::channel('stderr')->info($request->nome);
        //activity()->log('Look mum, I logged something');

        $input = $request->except(['nome','cognome']);
        $input['userId'] = $request->id;
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'].' '.$input['cognome'];

        User::find($id)->update($input);
        $user = User::find($id);
        LogActivity::addToLog('Edit User', ['avatar'=>$user->avatar,'full_name'=>$user->full_name],'success','edit');

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Modificato',
                'color' => 'success'
            ]
        );
    }

    public function view($id){

        $user = User::find($id);

        return response()->json(['user'=> $user]);
    }

    public function userOnLine($id){

        return response()->json(['online'=> (Cache::has('user-is-online-' . $id) ? true:false)]);

    }

    public function usersOnline (){
        $userOnline = 0;
        foreach (User::all()->pluck('id')->toArray() as $id){
            if((Cache::has('user-is-online-' . $id)))
                $userOnline++;
        }

        return response()->json(['online'=> $userOnline]);
    }

    public function totalUsers(Request $request){

        $users = DB::table('users')->Where(function ($query) use ($request) {
            if (!empty($request->activity))
                $query->Where('stato', '=', 1);
        })->count();

        return response()->json(['totalUsers'=> $users]);
    }

    public function delete($id){

        $user = User::find($id);
        $user->stato = 0;
        $user->save();

        LogActivity::addToLog('Deleted User ', ['avatar'=>$user->avatar,'full_name'=>$user->full_name],'error','deleted');


        return response()->json(
            [
                'success' => true,
                'message' => 'User Created'
            ]
        );
    }

    public function activities($id)
    {
        return response()->json(LogActivity::where('user_id', $id)->orderBy('id', 'DESC')->take(10)->get());
    }

    public function getUsersPermission(Request $request){
        $users = [];
        if($request->permission)
            $users = User::permission($request->permission)->get();


        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

}
