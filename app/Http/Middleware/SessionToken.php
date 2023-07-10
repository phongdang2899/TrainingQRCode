<?php

namespace App\Http\Middleware;

use App\Models\Watcher;
use App\Repositories\ConfigRepository;
use App\Repositories\WatcherRepository;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Http\Response;

class SessionToken
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
        // $uuid = Uuid::uuid4()->toString();
        // $ip = $request->ip();
        // $configRepository = new ConfigRepository;
        // $expiredAfter = $configRepository->getConfigByEntity('failed_ip', 'expired_after');
        // $expiredValue = $expiredAfter ? $expiredAfter->value : 1;
        // $watcherRepository = new WatcherRepository;
        // $watcher = $watcherRepository->getWatcherByIpAndPhone($ip, $request->phone_number);
        // $updatedAt = Carbon::parse($watcher->updated_at);
        // $updateAtAfter = $updatedAt->addMinutes($expiredValue);
        // if(empty($watcher))
        // {
        //     Watcher::create([
        //         'ip' => $ip,
        //         'phone_number' => $request->phone_number,
        //         'token' => $uuid
        //     ]);
        //     return $next($request);
        // }
        // if ($watcher->token != $uuid || $updateAtAfter < Carbon::now())
        // {
        //     return response()->json([
        //         'status' => 'FAIL',
        //         'message' => [trans('message.txt_miss_token')]
        //     ], Response::HTTP_FORBIDDEN);
        // }
        // $watcher->update([
        //     'token' => $uuid
        // ]);
        return $next($request);
    }
}
