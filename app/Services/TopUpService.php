<?php


namespace App\Services;

use App\Models\ThirdPartyTransaction;
use App\Repositories\ConfigRepository;
use App\Traits\Language;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use phpseclib\Crypt\RSA;

class TopUpService
{
    use Language;
    protected $apiGetTopup = "https://topup3.fibo.vn/api/v1/topup-fibo/getTopup";
    protected $apiAuth = "https://topup3.fibo.vn/api/v1/topup-fibo/getToken";
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
    public function callTopUpApi($transactionId, $codeId, $phone, $value)
    {
        if (!App::environment('production')) {
            return true;
        }
        try {
            $token = $this->configRepository->getConfigByEntity('topup_api', 'token');
            $tokenExpiredAt = $this->configRepository->getConfigByEntity('topup_api', 'token_expires_at');
            $tokenVal = $token ? $token->value : '';
            $tokenExpiredAtVal = $tokenExpiredAt ? Carbon::parse($tokenExpiredAt->value) : Carbon::now();
            if (!$tokenVal || $tokenExpiredAtVal <= Carbon::now()->subMinutes(3)) {
                $tokenVal = $this->getToken();
                if (!$tokenVal) {
                    return false;
                }
            }
            $tokenRsa = $this->configRepository->getConfigByEntity('topup_api', 'public_key_rsa');
            $telCo = $this->configRepository->getConfigByEntity('topup_api', 'telco');
            $tokenRsaVal = $tokenRsa ? $tokenRsa->value : '';
            $telCoVal = $telCo ? $telCo->value : '';

            if (!$tokenRsaVal || !$telCoVal) {
                return false;
            }
            $data = json_encode([
                "phoneNumber" => $phone,
                "cardValue" => strval($value),
//                "telco" => $telCoVal,
                "requestId" => $transactionId . '-' . $codeId . '-' . Carbon::now()->getTimestamp(),
                "postbackUrl" => "NONE"
            ]);
            // log
            $thirdLog = new ThirdPartyTransaction();
            $thirdLog->createLog('top-up', $data);
            $rsa = new RSA();
            $rsa->loadKey($tokenRsaVal);
            $rsa->setEncryptionMode(2);
            $output = $rsa->encrypt($data);
            $encryptData = base64_encode($output);
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
                ->withToken($tokenVal)
                ->post($this->apiGetTopup, ['payload' => $encryptData]);
            if (!$response->body()) {
                $thirdLog->updateLogError();
                return false;
            }
            $thirdLog->updateLog($response->body());
            $responseBody = json_decode($response->body());
            if (!$responseBody->success) {
                $thirdLog->updateLogFail();
                return false;
            }
            return true;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return false;
        }
    }

    private function getToken()
    {
        try {
            $user = $this->configRepository->getConfigByEntity('topup_api', 'username');
            $pass = $this->configRepository->getConfigByEntity('topup_api', 'password');
            $userVal = $user ? $user->value : '';
            $passVal = $pass ? $pass->value : '';
            if (!$userVal || !$passVal) {
                return false;
            }

            $response = Http::post($this->apiAuth, [
                "usernameAPI" => $userVal,
                "passwordAPI" => $passVal
            ]);
            if (!$response->body()) {
                return false;
            }
            $responseBody = json_decode($response->body());
            if (!$responseBody->success || !$responseBody->data || !$responseBody->data->token || !$responseBody->data->token_expires_at) {
                return false;
            }
            $token = $this->configRepository->getConfigByEntity('topup_api', 'token');
            $tokenExpiredAt = $this->configRepository->getConfigByEntity('topup_api', 'token_expires_at');

            if (empty($token)) {
                $newToken = $this->configRepository->create([
                    'entity_id' => 'topup_api',
                    'entity_type' => 'token',
                    'value' => $responseBody->data->token,
                    'status' => '1',
                    'created_by' => '1',
                    'updated_by' => '1',
                ]);
            } else {
                $newToken = $this->configRepository->update($token->id, ['value' => $responseBody->data->token]);
            }
            if (empty($tokenExpiredAt)) {
                $newExpriredToken = $this->configRepository->create([
                    'entity_id' => 'topup_api',
                    'entity_type' => 'token_expires_at',
                    'value' => $responseBody->data->token_expires_at,
                    'status' => '1',
                    'created_by' => '1',
                    'updated_by' => '1',
                ]);
            } else {
                $newExpriredToken = $this->configRepository->update($tokenExpiredAt->id, ['value' => $responseBody->data->token_expires_at]);
            }
            if (!$newToken || !$newExpriredToken) {
                return false;
            }
            return $responseBody->data->token;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return false;
        }
    }
}
