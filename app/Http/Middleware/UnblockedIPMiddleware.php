<?php

namespace App\Http\Middleware;

use App\Models\Watcher;
use App\Repositories\ConfigRepository;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UnblockedIPMiddleware
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
        $watcher = Watcher::where('ip', $ip)->where('phone_number', $request->phone_number)->first();
        $configRepository = new ConfigRepository;
        $limit = $configRepository->getConfigByEntity('failed_ip', 'limit');
        $limitVal = $limit ? $limit->value : 5;
        $unblockAfter = $configRepository->getConfigByEntity('failed_ip', 'unblock_after');
        if (!empty($watcher) && $watcher->previous_time && $watcher->times >= $limitVal) {
            $blockedAt = Carbon::parse($watcher->previous_time);
            $unblockAfterVal = $unblockAfter ? $unblockAfter->value : 10;
            $unblockAt = $blockedAt->addMinutes($unblockAfterVal);
            if ($unblockAt > Carbon::now()) {
                return response()->json([
                    'status' => 'FAIL',
                    'message' => [trans('message.txt_ip_is_blocked')],
                    'error_code' => config('constants.error_code.ip_blocked_phonecheck')
                ], Response::HTTP_FORBIDDEN);
            } else {
                $watcher->update([
                    'times' => 0,
                    'previous_time' => Carbon::now()
                ]);
            }
        }
        return $next($request);
    }
}
