<?php


namespace App\Services;

use App\Models\ThirdPartyTransaction;
use App\Repositories\ConfigRepository;
use App\Traits\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    use Language;
    protected $apiUrl = "https://brandapi.gapit.com.vn/V1/SendMt";
    protected $configRepository;
    public function __construct()
    {
        $this->configRepository = new ConfigRepository;
    }

    /**
     * Call api to send SMS
     *
     * @return boolean
     */
    public function sendSMS($phone, $otp)
    {
        if (!App::environment('production') || true) {
            return true;
        }
        try {
            $user = $this->configRepository->getConfigByEntity('sms_api', 'user');
            $pass = $this->configRepository->getConfigByEntity('sms_api', 'pass');
            $cpId = $this->configRepository->getConfigByEntity('sms_api', 'cp_id');
            $cpName = $this->configRepository->getConfigByEntity('sms_api', 'cp_name');
            $brandName = $this->configRepository->getConfigByEntity('sms_api', 'brand_name');
            $msgBody = $this->configRepository->getConfigByEntity('sms_api', 'msg_body');
            $expiredAfter = $this->configRepository->getConfigByEntity('otp', 'expired_after');

            $userVal = $user ? $user->value : '';
            $passVal = $pass ? $pass->value : '';
            $cpIdVal = $cpId ? $cpId->value : '';
            $cpNameVal = $cpName ? $cpName->value : '';
            $brandNameVal = $brandName ? $brandName->value : '';
            $msgBodyVal = $msgBody ? $msgBody->value : '';
            $expiredValue = $expiredAfter ? $expiredAfter->value : 1;
            if (!$cpIdVal || !$cpNameVal || !$userVal || !$passVal || !$brandNameVal || !$msgBodyVal) {
                return false;
            }
            $message = str_replace(["{OTP}", "{expired_time}"], [$otp, $expiredValue], $msgBodyVal);
            $messageStripVN = $this->stripVN($message);
            // log
            $dataToSend = [
                "dest" => $phone,
                "brandname" => $brandNameVal,
                "msgbody" => $messageStripVN,
                "content_type" => "text",
                "serviceid" => $cpNameVal,
                "cpid" => $cpIdVal
            ];
            $thirdLog = new ThirdPartyTransaction();
            $thirdLog->createLog('brand-name', json_encode($dataToSend));
            $response = Http::withBasicAuth($userVal, $passVal)->post($this->apiUrl, $dataToSend);
            if (!$response->body()) {
                $thirdLog->updateLogError();
                return false;
            }
            $responseBody = json_decode($response->body());
            if ($responseBody->status != 200) {
                $thirdLog->updateLogFail();
                return false;
            }
            $thirdLog->updateLog($response->body());
            return true;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return false;
        }
    }
}
