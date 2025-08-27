<?php

namespace App\Http\Controllers;

use App\Models\SyFolderShared;
use Illuminate\Http\Request;

class SyFolderSharedController extends Controller
{
    public function index(Request $request)
    {
        $sortByName = $request->get('sortBy');
        $orderBy = $request->get('orderBy');
        $titoloBy = $request->get('titolo');
        $utenteBy = $request->get('utente');

        if (empty($sortByName)) {
            $sortByName = 'titolo';
            $orderBy = 'asc';
        }
        if(empty($utenteBy)){
            $objs = SyFolderShared::Where(function ($query) use ($titoloBy) {
                    if ($titoloBy) {
                        $query->Where('titolo','LIKE', '%'.$titoloBy.'%');
                    }
                })
                ->orderBy($sortByName, $orderBy)
                ->paginate($request->itemsPerPage);
        }else{
            $objs = SyFolderShared::query()
                ->join('sy_users_folders_shareds','sy_users_folders_shareds.folder_id','sy_folder_shareds.id')
                ->join('users','users.id','sy_users_folders_shareds.user')
                ->select('sy_users_folders_shareds.*',)
                ->Where(function ($query) use ($titoloBy) {
                    if ($titoloBy) {
                        $query->Where('titolo','LIKE', '%'.$titoloBy.'%');
                    }
                })
                ->where('sy_users_folders_shareds.user',$utenteBy)
                ->orderBy($sortByName, $orderBy)
                ->paginate($request->itemsPerPage);
        }


        return response()->json($objs);
    }
}
