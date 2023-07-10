<?php

namespace App\Http\Middleware;

use App\Models\OtpTracking;
use App\Repositories\ConfigRepository;
use App\Repositories\CustomerRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class UnblockedSystemOTPMiddleware
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
        $customer = new CustomerRepository();
        $customer = $customer->checkPhoneEndUser($request->phone);
        if(!empty($customer)){
            $otpTracking = OtpTracking::where('ip', $ip)->where('customer_id', $customer->id)->first();
            $configRepository = new ConfigRepository;
            $limit = $configRepository->getConfigByEntity('otp', 'limit');
            $limitVal = $limit ? $limit->value : 3;
            $unblockAfter = $configRepository->getConfigByEntity('otp', 'unblock_after');
            if (!empty($otpTracking) && $otpTracking->updated_at && $otpTracking->times >= $limitVal) {
                $blockedAt = Carbon::parse($otpTracking->updated_at);
                $unblockAfterVal = $unblockAfter ? $unblockAfter->value : 24;
                $unblockAt = $blockedAt->addHours($unblockAfterVal);
                $unblockAt = Carbon::parse($unblockAt);
                if ($unblockAt > Carbon::now()) {
                    return response()->json([
                        'status' => 'FAIL',
                        'message' => [trans('message.txt_locked_account')],
                        'time_blocked' => $blockedAt,
                        'unblock_at' => $unblockAt,
                        'error_code' => config('constants.error_code.account_locked_phonevalidate'),
                    ], Response::HTTP_FORBIDDEN);
    
                } else {
                    $otpTracking->update([
                        'times' => 0
                    ]);
                }
            }
        }
        return $next($request);
    }
}
