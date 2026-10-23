<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class CheckTokenExpiration
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
        $token = $request->access_token;
        
        if ($token) {
            $tokenData = PersonalAccessToken::findToken($token);

            if (!$tokenData || $tokenData->expires_at->lt(Carbon::now())) {
                return response()->json(['message' => 'Token is expired'], 401);
            }
        } else {
            return response()->json(['message' => 'Token not provided'], 401);
        }

        return $next($request);
    }
}
