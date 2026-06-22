<?php

namespace App\Http\Controllers;

use App\Models\LogActivity;
use App\Jobs\FatturatoEmail;
use App\Models\RecipientCoordinate;
use App\Models\User;
use App\Services\GoogleDrive;
use App\Services\GoogleSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Revolution\Google\Sheets\Facades\Sheets;
use App\Models\Utility;
use App\Models\QtCheckerReport;
use App\Imports\WorkStatusImport;
use Maatwebsite\Excel\Facades\Excel;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Print\TemplateZpl;
use App\Jobs\TaskNotifiche;
use App\Jobs\CredenzialiWifi;



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
		//Dispatch(new CredenzialiWifi('A04C600C-E503-4811-B6C2-738C4FAF0663'));
		/*$info = [
                'nome' => 'Visitatore',
                'azienda' => 'Stl',
                'email' => 'gregorio.grande@stl.tech',
            ];
			Mail::send('emails/email_visitatore_arrivato', compact('info'), function ($message) use ($info) {
            $message
                ->to('portale.metallurgica@stl.tech')
                    ->subject('Notifica Visitatore.');
        });*/
            
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

             $image = QrCode::format('png')
                 ->size(200)->errorCorrection('H')
                 ->generate('A simple example of QR code!');
             $output_file = '/qrcode-' . time() . '.png';
             //Log::channel('stderr')->info($image->image);
             //Storage::disk('public')->put($output_file, $image);

             //request()->file->move(public_path('workflow/' . $workflow->commessa), $nameFile);



             Storage::disk('ftp')->put("qrcode_portale/" . $output_file, $image);

             //Log::channel('stderr')->info($image);
             $content = '';
             Mail::send('emails/email_test', compact('output_file'), function ($message) {
                 $message
                     ->to(['gregorio.grande@stl.tech'])
                     ->subject('test QRCODE');
             });

   
  
	 
		$file = storage_path('app/cavi.xlsx');
        $file = Excel::toArray(new WorkStatusImport, $file);
        foreach ($file as $rows) {
            foreach ($rows as $row) {
                if(!empty($row[3])){
					//Log::info($row[1]);
                    $obj = ToQuoteCable::where('codice','=',(string)$row[1])->first();
                    
					if(!empty($obj->id)){
						$obj->norma = $row[3];
						$obj->save();
					}
						
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

        $input = $request->except(['password', 'nome', 'cognome', 'id','username']);
        $input['password'] = Hash::make($request->password);
        $input['nome'] = ucfirst(strtolower($request->nome));
        $input['cognome'] = ucfirst(strtolower($request->cognome));
        $input['full_name'] = $input['nome'] . ' ' . $input['cognome'];
		$input['username'] = (empty($request->username) ? $request->email:$request->username);
		
		

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
         $users = User::select('*')
            ->where('stato', 1)
            ->orderBy('nome')->get();

        return response()->json(
            [
                'success' => true,
                'data' => $users
            ]
        );
    }

    public function getVersion()
    {
        $version = file_exists(base_path('version.txt')) ? trim(file_get_contents(base_path('version.txt'))) : '1.0.0';

        return response()->json(
            [
                'success' => true,
                'version' => $version
            ]
        );
    }
	
	public function mensa(){
		stream_context_set_default(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]);
		$path_file = '/public/file/';
        $day_of_week = date("N") - 5;
        $primo_giorno =  strtotime("-$day_of_week days");
        $path = 'https://app.metallurgicabresciana.it/turni/mb/menza/api/get.php?';
        //$path.= 'time='.date('Y-m-d',$primo_giorno);
        $path.= 'time='.date('Y-m-d');
        $getMovieList = file_get_contents($path);
        $result = json_decode($getMovieList);
		$html = '<table style="width:100%">
				  <tr>
					<th>Pientanza</th>
					<th>Dipendente</th>
					<th>Data</th>
				  </tr>';

        $spreadsheet  = new Spreadsheet();
        $activeWorksheet = $spreadsheet->getActiveSheet();


        $activeWorksheet->setCellValue('A1', 'Pientanza');
        $activeWorksheet->setCellValue('B1', 'Matricola');
        $activeWorksheet->setCellValue('C1', 'Dipendente');
        $activeWorksheet->setCellValue('D1', 'Data');
        $activeWorksheet->setCellValue('E1', 'Costo');
        $i = 2;
        foreach ((array)$result->list as $row){
			$html.=' <tr>
						<td>'.$row[0].'</td>
						<td>'.$row[2].'</td>
						<td>'.$row[3].'</td>
					  </tr>';
            $activeWorksheet->setCellValue('A'.$i, $row[0]);
            $activeWorksheet->setCellValue('B'.$i, $row[1]);
            $activeWorksheet->setCellValue('C'.$i, $row[2]);
            $activeWorksheet->setCellValue('D'.$i, $row[3]);
            $activeWorksheet->setCellValue('E'.$i, $row[4]);
            $i++;
        }
		$html.= '</table>';
        $writer = new Xlsx($spreadsheet);

        $file_dir = dirname(__DIR__, 3).$path_file;
        if (!file_exists($file_dir)) {
            if (!mkdir($file_dir, 0777, true) && !is_dir($path_file)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $path_file));
            }
        }
        $file = $file_dir.date('Y_m_d_').'.xlsx';
        $writer->save($file);

        //$file = public_path('file/'.date('Y_m_d').'.xlsx');

        $users = Utility::users_notify(['mensa_week']);

        Mail::send('emails/email_mensa', compact('html'), function ($message) use($file, $primo_giorno, $users){
            $message
                ->to($users)
                ->subject('Mensa Metallurgica Bresciana Del '. date('Y-m-d'));

            $message->attach( $file);
        });
        File::delete($file);
	}

}
