<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Login user and create token
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [boolean] remember_me
     */

    public function login(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            'remember_me' => 'boolean'
        ]);

        $credentials = request(['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = $request->user();

        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;
        $perissions = [];
        $perissions_objs = $user->getAllPermissions();
        $perissions[] =  ['action' => 'manage', 'subject' => 'Administration'];


        foreach ($perissions_objs as $obj){
            $tmp = explode(".",$obj->name);
            $perissions[] = ['action' => $tmp[1], 'subject' => $tmp[0]];
        }

        return response()->json([
            'userAbilityRules' => $perissions,
            'userData' => ['id' => $user->id,
                'fullName' => $user->full_name,
                'username' => $user->nome,
                'avatar' => env('BASE_URL', null).$user->avatar,
                'email' => $user->email,
                'role' => 'admin',],
            'accessToken' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);

    }
}
