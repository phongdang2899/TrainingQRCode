<?php

namespace App\Repositories;

use App\Models\OtpTracking;
use Carbon\Carbon;

class OtpTrackingRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return OtpTracking::class;
    }

    public function getOtpTrackingByCustomerId(int $customerId, string $ip)
    {
        return $this->_model->select('*')->where('customer_id', $customerId)->where('ip', $ip)->first();
    }

    public function createOtpTracking(int $customerId, string $ip, int $otp, int $times)
    {
        return $this->_model->create([
            'customer_id' => $customerId,
            'ip' => $ip,
            'active_code' => $otp,
            'times' => $times,
            'activated_at' => Carbon::now()
        ]);
    }

    public function updateOtpTracking(int $customerId, string $ip, int $otp, int $times, int $otpOld, $previous_time)
    {
        $otpTracking = $this->_model->where('customer_id', $customerId);
        $otpTracking->update([
            'ip' => $ip,
            'active_code' => $otp,
            'times' => $times,
            'activated_at' => Carbon::now(),
            'previous_code' => $otpOld,
            'previous_time' => $previous_time
        ]);
        return $otpTracking;
    }

    public function updateByTimes(int $time, int $customerId, string $ip)
    {
        $otpTracking = $this->_model->where('customer_id', $customerId)->where('ip', $ip);
        $otpTracking->update([
            'times' => $time,
        ]);
        return $otpTracking;
    }
}
