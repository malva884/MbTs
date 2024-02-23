<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;

class LogApiActivity
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $allow_path = [
            'api/users/edit',

        ];
        Log::channel('stderr')->info($this->getActivityDescription($request));
        if ($request->isMethod('POST') && ($request->is('api/*') && Auth::check())) {

            $user = Auth::user();
            $activityDescription = $this->getActivityDescription($request);

            // Log the activity

            activity()
                ->performedOn($user) // You can change this to the model you are working with
                ->withProperties(['description' => $activityDescription])
                ->log('api_action');

        }

        return $response;
    }

    private function getActivityDescription($request)
    {
        // You can customize this based on your needs
        return $request->method() . ' ' . $request->path();
    }
}
