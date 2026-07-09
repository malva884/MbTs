<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;

class CheckSessionLifetime
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function handle(Request $request, Closure $next)
    {
        // Get session lifetime from database
        $sessionLifetime = $this->settingService->get('session_lifetime', 120);
        
        // Sync Laravel's session lifetime with database value
        Config::set('session.lifetime', $sessionLifetime);
        
        if (Session::has('last_activity')) {
            $lastActivity = Session::get('last_activity');
            
            $inactiveTime = now()->diffInMinutes($lastActivity);
            
            if ($inactiveTime >= $sessionLifetime) {
                Session::flush();
                return response()->json(['message' => 'Session expired'], 419);
            }
        }
        
        $response = $next($request);
        
        // Update last activity only after the request is processed
        Session::put('last_activity', now());
        
        return $response;
    }
}
