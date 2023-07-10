<?php

namespace App\Http\Middleware;

use App\Repositories\ConfigRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhitelistIP
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
        $ip = $request->ip();
        $configRepository = new ConfigRepository;
        $whitelistIP = $configRepository->getConfigByEntity('3rd', 'whitelist_ip');
        $whitelistIPVal = $whitelistIP ? explode(",", $whitelistIP->value) : [];
        if (!$whitelistIPVal && !in_array($ip, $whitelistIPVal)) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_permission_failure')]
            ], Response::HTTP_FORBIDDEN);
        }
        return $next($request);
    }
}
