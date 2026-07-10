<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Guard;
use Laravel\Sanctum\PersonalAccessToken;

class BearerTokenAuth
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
        $tokenString = $request->bearerToken();

        if ($tokenString) {
            // Trova il token nel database
            $accessToken = PersonalAccessToken::findToken($tokenString);

            if ($accessToken && (!$accessToken->expires_at || $accessToken->expires_at->isFuture())) {
                $user = $accessToken->tokenable;
                if ($user) {
                    Auth::setUser($user);
                    $request->setUserResolver(function () use ($user) {
                        return $user;
                    });
                }
            }
        }

        return $next($request);
    }
}
