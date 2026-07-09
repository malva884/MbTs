<?php

namespace App\Http\Middleware;

use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SetSessionLifetime
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get session lifetime from database
        $sessionLifetime = $this->settingService->get('session_lifetime', 120);
        
        Log::info('SetSessionLifetime middleware - session_lifetime from DB: ' . $sessionLifetime);
        Log::info('SetSessionLifetime middleware - current config session.lifetime: ' . config('session.lifetime'));
        
        // Set the session lifetime dynamically
        config(['session.lifetime' => (int) $sessionLifetime]);
        
        Log::info('SetSessionLifetime middleware - updated config session.lifetime: ' . config('session.lifetime'));
        
        return $next($request);
    }
}
