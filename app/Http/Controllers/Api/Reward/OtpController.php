<?php

namespace App\Http\Controllers\Api\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerSendOTPRequest;
use App\Models\Code;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OtpTrackingRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\WatcherRepository;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Http\Response;

class OtpController extends Controller
{
    protected $customerRepository;
    protected $codeRepository;
    protected $campaignRepository;
    protected $userRepository;
    protected $transactionRepository;
    protected $configRepository;
    protected $otpTrackingRepository;
    protected $watcherRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository,
        UserRepository $userRepository,
        TransactionRepository $transactionRepository,
        ConfigRepository $configRepository,
        OtpTrackingRepository $otpTrackingRepository,
        WatcherRepository $watcherRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->configRepository = $configRepository;
        $this->otpTrackingRepository = $otpTrackingRepository;
        $this->watcherRepository = $watcherRepository;
    }

    /**
     * @OA\Post(
     *      path="/enduser/phone/otp",
     *      operationId="checkPhoneByOTP",
     *      tags={"EndUsers"},
     *      summary="Check phone is valid and send OTP",
     *      description="Return data code and transaction Id",
     *      @OA\Parameter(
     *          name="phone_number",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="codes[]",
     *          description="Check code is valid",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *       @OA\Response(
     *         response=404,
     *         description="Resource Not Found"
     *   ),
     * )
     */
    /**
     * Check code is valid and send OTP
     *
     * @param CustomerSendOTPRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOTP(CustomerSendOTPRequest $request)
    {
        $params = [
            'phone_number'  => $request->phone_number,
            'codes'         => $request->codes,
        ];

        $errorArr = [];
        $successArr = [];
        $campaign = $this->campaignRepository->findByField('code', $request->header('campaign'))->first();
        foreach ($params['codes'] as $code) {
            $codeDetails = $this->codeRepository->getCodeByCodeAndCampaign($code, $campaign->id);
            if (empty($codeDetails)) {
                $errorArr[] = [
                    'code' => $code,
                    'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                ];
            } else {
                if ($codeDetails->status == Code::STATUS_NEW) {
                    $successArr[] = [
                        'code' => $codeDetails->code,
                        'value' => $codeDetails->value
                    ];
                } else {
                    $errorArr[] = [
                        'code' => $codeDetails->code,
                        'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                    ];
                }
            }
        }
        if (empty($successArr)) {
            return $this->responseErrorCode(
                trans('message.txt_invalid', ['attribute' => trans('message.code')]),
                config('constants.error_code.code_invalid_phoneotp')
            );
        }

        $phoneNumber = $this->customerRepository->checkPhoneEndUser($params['phone_number']);
        if (empty($phoneNumber)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found_please_create_new_customer', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_phoneotp'),
                Response::HTTP_NOT_FOUND
            );
        }

        $customerId = $phoneNumber->id;
        $ip = $request->ip();

        $transaction = $this->createTransaction($ip, $params['codes'], $customerId);
        if (!$transaction) {
            return $this->responseErrorCode(
                trans('message.txt_created_transaction_failure'),
                config('constants.error_code.create_transaction_failed_phoneotp')
            );
        }

        $otp = rand(1000, 999999);
        $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
        $limit = $this->configRepository->getConfigByEntity('otp', 'limit');
        $limitVal = $limit ? $limit->value : 3;

        $otpTracking = $this->otpTrackingRepository->getOtpTrackingByCustomerId($customerId, $ip);
        if(empty($otpTracking)){
            $otpTrackingCreated = $this->otpTrackingRepository->createOtpTracking($customerId, $ip, $otp, $times = 1);
            $time_number_curr = $limitVal -1;
        } else if ($otpTracking && $otpTracking->times >= $limitVal-1){
            $this->otpTrackingRepository->updateByTimes($otpTracking->times + 1, $customerId, $ip);
            return $this->responseErrorCode(
                trans('message.txt_locked_account'),
                config('constants.error_code.account_locked_phoneotp'),
                Response::HTTP_FORBIDDEN
            );
        } else if($otpTracking && $otpTracking->times < $limitVal ){
            $time_number_curr = $limitVal -1 - $otpTracking->times;
        }

        if (!empty($otpTracking) && $otpTracking->active_code != $otp) {
            $ExpiredTime = config('constants.timelife_otp.one');
            $times = $otpTracking->times + $ExpiredTime;
            $otpOld = $otpTracking->active_code;
            $previous_time = $otpTracking->activated_at;
            $this->otpTrackingRepository->updateOtpTracking($customerId, $ip, $otp, $times, $otpOld, $previous_time);
        }

        $smsService = new SMSService();
        $sentSMS = $smsService->sendSMS($params['phone_number'], $otp);
        if (!$sentSMS) {
            return $this->responseErrorCode(
                trans('message.txt_send_otp_failure'),
                config('constants.error_code.send_otp_failed_phoneotp')
            );
        }

        $expiredAfter = $this->configRepository->getConfigByEntity('otp', 'expired_after');
        $expiredAfterValue = $expiredAfter ? $expiredAfter->value : 180;

        return $this->responseSuccess([
            'message' => trans('message.txt_send_otp_successfully'),
            'transaction_id' => $transaction,
            'data' => $successArr,
            'timeLife' => $expiredAfterValue,
            'current_number' => $time_number_curr
        ]);
    }

    private function createTransaction($ip, $codes, $customer_id)
    {
        $transactionCreated = $this->transactionRepository->createTransaction($ip, $customer_id, 'internal');
        if (empty($transactionCreated)) {
            return false;
        }
        foreach ($codes as $code) {
            $codeDetails = $this->codeRepository->findByField('code', $code)->first();
            if (empty($codeDetails)) {
                return $this->responseError(
                    trans('message.txt_not_found', ['attribute' => trans('message.code')]),
                    Response::HTTP_NOT_FOUND
                );
            }
            $transItemCreated = $this->transactionRepository->createTranItem($transactionCreated->id, $codeDetails->id, $codeDetails->value);
            if (empty($transItemCreated)) {
                return false;
            }
        }
        return $transactionCreated->id;
    }
}
