<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class SystemUser
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
        $authUser = Auth::user();
        if (!$authUser->hasPermission('system')) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_permission_failure')]
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
