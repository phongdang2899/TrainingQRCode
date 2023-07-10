<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class NotSystemUser
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
        if ($authUser->role_id == config('constants.roles.system.key') || $authUser->status != User::STATUS_ACTIVE) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_permission_failure')]
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
