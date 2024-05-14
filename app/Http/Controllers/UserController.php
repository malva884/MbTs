<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Models\RecipientCoordinate;
use App\Models\Target;
use App\Models\User;
use App\Models\Utility;
use App\Services\GoogleDrive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Revolution\Google\Sheets\Facades\Sheets;

class UserController extends Controller
{
    public function __construct()
    {
        //$this->middleware('auth', ['only' => ['create', 'store', 'edit', 'delete']]);
        // Alternativly
        // $this->middleware('auth', ['except' => ['index', 'show']]);
    }

    public function index(Request $request)
    {
     /*   $rows = Sheets::spreadsheet('1t8I7GA7QMNFrnf0wmeJQzG0ttqeezaT41nm8Niu45jg')->sheet('Foglio1')->all();

        foreach ($rows as $key => $row){
            if($key > 0 && $row[0]){
                $periodo = $row[4].'-'.$row[3].'-01';
                $obj = DB::table('targets')
                    ->where('data_riferimento', $periodo)
                    ->where('tipo',$row[2])
                    ->where('titolo',$row[0])
                    ->first();
                if(empty($obj->id)){
                    $obj = new Target();
                    $obj->titolo = $row[0];
                    $obj->data_riferimento = $periodo;
                    $obj->tipo = $row[2];
                    $terg = str_replace(",",'',$row[1]);
                    $obj->target = ($row[1] ? number_format($terg, 3, '.', ''):0.000);
                    $obj->user = 1;
                    $obj->valore = 0.000;
                    $obj->save();
                }

            }
        }
*/
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $userBy = $request->get('user');
        $roleBy = $request->get('role');
        $statoBy = $request->get('stato');


        if (empty($sortByName)) {
            $sortByName = 'full_name';
            $orderBy = 'asc';
        }

        $users = DB::table('users')
            ->Where(function ($query) use ($userBy) {
                if ($userBy)
                    $query->Where('full_name', 'LIKE', '%' . $userBy . '%');
            })
            ->Where(function ($query) use ($roleBy) {
                if ($roleBy)
                    $query->Where('role', '=', $roleBy);
            })
            ->Where(function ($query) use ($statoBy) {
                if ($statoBy)
                    $query->Where('stato', $statoBy);
            })
            ->orderBy($sortByName, $orderBy) //order in descending order
            ->paginate($request->itemsPerPage);


        return response()->json($users);

    }

    public function store(Request $request)
    {

        $request->validate([
            'nome' => 'required',
            'cognome' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
            'role' => 'required',
            'stato' => 'required',
            'lingua' => 'required',
        ]);

        $input = $request->except(['password', 'nome', 'cognome', 'id']);
        $input['password'] = Hash::make($request->password);
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'] . ' ' . $input['cognome'];

        $user = User::create($input);

        if ($user->sesso == 'm')
            $user->avatar = 'images/avatars/m_' . rand(1, 4) . '.png';
        else
            $user->avatar = 'images/avatars/f_' . rand(1, 4) . '.png';
        $user->password_changed_at = Date('Y-m-d H:i:s');
        $user->save();
        $user->assignRole($request->input('role'));

        LogActivity::addToLog('New User ', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'info', 'new');


        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Creato',
                'color' => 'success'
            ]
        );
    }

    public function update(Request $request, $id)
    {
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

        $input = $request->except(['nome', 'cognome']);
        $input['userId'] = $request->id;
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'] . ' ' . $input['cognome'];

        User::find($id)->update($input);
        $user = User::find($id);
        LogActivity::addToLog('Edit User', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'success', 'edit');

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Utente-Modificato',
                'color' => 'success'
            ]
        );
    }

    public function view($id)
    {

        $user = User::find($id);

        return response()->json(['user' => $user]);
    }

    public function userOnLine($id)
    {

        return response()->json(['online' => (Cache::has('user-is-online-' . $id) ? true : false)]);

    }

    public function usersOnline()
    {
        $userOnline = 0;
        foreach (User::all()->pluck('id')->toArray() as $id) {
            if ((Cache::has('user-is-online-' . $id)))
                $userOnline++;
        }

        return response()->json(['online' => $userOnline]);
    }

    public function totalUsers(Request $request)
    {

        $users = DB::table('users')->Where(function ($query) use ($request) {
            if (!empty($request->activity))
                $query->Where('stato', '=', 1);
        })->count();

        return response()->json(['totalUsers' => $users]);
    }

    public function reset_password(Request $request, $id)
    {
        $user = User::find($id);
        $user->password = Hash::make($request->password);
        $user->password_changed_at = null;
        $user->save();

        LogActivity::addToLog('Reset Password User', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'success', 'edit');

        return response()->json(
            [
                'success' => true,
                'message' => 'Messaggi.Password-Resettata',
                'color' => 'success'
            ]
        );
    }

    public function delete($id)
    {

        $user = User::find($id);
        $user->stato = 0;
        $user->save();

        LogActivity::addToLog('Deleted User ', ['avatar' => $user->avatar, 'full_name' => $user->full_name], 'error', 'deleted');


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

    public function getUsersPermission(Request $request)
    {
        $users = [];
        if ($request->permission)
            $users = User::permission($request->permission)->get();


        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

    public function getUsers()
    {
        $users = User::all()
            ->where('stato', 1);

        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

}
