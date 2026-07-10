<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ImpersonationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        
        if ($user) {
            // Ottieni il token dalla richiesta
            $tokenString = $request->bearerToken();
            
            if ($tokenString) {
                // Verifica se c'è una sessione di impersonazione attiva
                $impersonationData = Cache::get('impersonation_' . $tokenString);
                
                if ($impersonationData) {
                    // Aggiunge informazioni di impersonazione alla richiesta
                    $request->attributes->set('is_impersonating', true);
                    $request->attributes->set('impersonation_data', $impersonationData);
                    $request->attributes->set('original_admin_id', $impersonationData['admin_user_id']);
                }
            }
        }

        return $next($request);
    }
}
