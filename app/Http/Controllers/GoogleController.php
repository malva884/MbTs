<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\GoogleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
    protected $googleService;

    public function __construct(GoogleService $googleService)
    {
        $this->googleService = $googleService;
    }

    public function redirectToGoogle()
    {
        $authUrl = $this->googleService->getAuthUrl();
        return redirect()->away($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $token = $this->googleService->authenticate($request->get('code'));

        // Save the token to the user's profile or session
        $user = Auth::user();
        //$user = User::find(1);
        $user->google_token = json_encode($token);
        $user->save();

        return redirect()->route('dashboard')->with('success', 'Gmail connected successfully');
    }

    public function listMessages()
    {
        //$user = Auth::user();
        $user = User::find(1);
        $token = json_decode($user->google_token, true);
        $this->googleService->setAccessToken($token);

        $messages = $this->googleService->listMessages();
        foreach ($messages as $message)
            Log::channel('stderr')->info($message->getSubject());
       // return view('emails.index', ['messages' => $messages]);
    }
}
