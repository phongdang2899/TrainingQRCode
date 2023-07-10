<?php

namespace App\Http\Controllers\Api\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodeRequest;
use App\Http\Requests\OTPPostRequest;
use App\Http\Resources\CodeErrorResource;
use App\Http\Resources\CustomerPhoneErrorResource;
use App\Http\Resources\CustomerTopupErrorResource;
use App\Http\Resources\CustomerTopUpSuccessResource;
use App\Models\Code;
use App\Models\Customer;
use App\Models\Transaction;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OtpTrackingRepository;
use App\Repositories\TopUpRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\WatcherRepository;
use Illuminate\Http\Response;
use Carbon\Carbon;

class RedeemController extends Controller
{
    protected $customerRepository;
    protected $codeRepository;
    protected $campaignRepository;
    protected $transactionRepository;
    protected $watcherRepository;
    protected $configRepository;
    protected $otpTrackingRepository;
    protected $topUpRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository,
        TransactionRepository $transactionRepository,
        WatcherRepository $watcherRepository,
        ConfigRepository $configRepository,
        OtpTrackingRepository $otpTrackingRepository,
        TopUpRepository $topUpRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
        $this->transactionRepository = $transactionRepository;
        $this->watcherRepository = $watcherRepository;
        $this->configRepository = $configRepository;
        $this->otpTrackingRepository = $otpTrackingRepository;
        $this->topUpRepository = $topUpRepository;
    }

    /**
     * @OA\Post(
     *      path="/enduser/redeem/retry",
     *      operationId="tryRedeem",
     *      tags={"EndUsers"},
     *      summary="Redeem again",
     *      description="Redeem again and returns content",
     *      @OA\Parameter(
     *          name="transaction_id",
     *          description="Transaction id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone_number",
     *          description="Phone number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */

    /**
     * Get reward again
     *
     * @param CodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function tryGetReward(CodeRequest $request)
    {
        $params = [
            'phone_number' => $request->phone_number,
            'transaction_id' => $request->transaction_id
        ];
        $errorArr = [];
        $successArr = [];
        $arrCodeId = [];
        $totalValue = 0;
        $customer = $this->customerRepository->findByField('phone_number', $params['phone_number'])->first();
        if (empty($customer) || ($customer->status != Customer::STATUS_ACTIVE)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_redeemretry'),
                Response::HTTP_NOT_FOUND
            );
        }
        $transaction = $this->transactionRepository->find($params['transaction_id']);
        if (empty($transaction)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction')]),
                config('constants.error_code.transaction_not_found_redeemretry'),
                Response::HTTP_NOT_FOUND
            );
        }
        $transactionItems = $this->transactionRepository->findByFieldTransItem('transaction_id', $params['transaction_id']);
        if (!count($transactionItems)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction_item')]),
                config('constants.error_code.transaction_item_not_found_phonevalidate'),
                Response::HTTP_NOT_FOUND
            );
        }
        foreach ($transactionItems as $transactionItem) {
            $codeDetail = $this->codeRepository->find($transactionItem->code_id);
            if (empty($codeDetail)) {
                $errorArr[] = [
                    'code' => $codeDetail->code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
            } else {
                $arrCodeId[] = $codeDetail->id;
                if ($codeDetail->status == Code::STATUS_PENDING) {
                    $successArr[] = [
                        'code' => $codeDetail->code,
                        'value' => $codeDetail->value
                    ];
                    // $totalValue += $codeDetail->value;
                } else {
                    $errorArr[] = [
                        'code' => $codeDetail->code,
                        'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                    ];
                }
            }
        }
        $redeemAllow = null;
        $limitedCount = 0;
        $topupRepo = new TopUpRepository();
        $codeData = $this->codeRepository->find($transactionItems[0]->code_id);
        $codeGroup = $this->codeRepository->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $topupRepo->validQuota($codeData->campaign_id, $key, count($codeValue), true);
            // if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
            //     $limitedCount += count($codeValue);
            // }
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                array_splice($codeValue, 0, $redeemAllow);
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                $limitedCount += count($codeValue);
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }
        foreach ($successArr as $v) {
            $totalValue += $v['value'];
        }
        if ($limitedCount == count($transactionItems)) {
            // show error limit redeem
            // return $this->responseError(
            //     trans('message.txt_out_of_quota')
            // );
            return [
                'data' => $errorArr,
                'failedMsd' => trans('message.txt_out_of_quota')
            ];
        }
        $topupRepo = new TopUpRepository();
        $callTopup = $topupRepo->redoTopUp($params['transaction_id']);
        if (!$callTopup) {
            return $this->responseErrorCode(
                trans('message.txt_top_up_failure'),
                config('constants.error_code.failed_top_up_redeemretry')
            );
        }
        return $this->responseSuccess([
            'message' => trans('message.txt_top_up_successfully'),
            'phone_number' => $params['phone_number'],
            'total_money' => $totalValue,
            'success_array' => $successArr,
            'error_array' => $errorArr,
        ]);
    }

    /**
     * @OA\Post(
     *      path="/enduser/phone/validate",
     *      operationId="validateOTP",
     *      tags={"EndUsers"},
     *      summary="validate OTP is valid ",
     *      description="Returns OTP validate successfully",
     *      @OA\Parameter(
     *          name="OTP",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
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
     *      @OA\Parameter(
     *          name="transaction_id",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
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
     * validate OTP from EndUser
     *
     * @param OTPPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyOTP(OTPPostRequest $request)
    {
        $params = [
            'phone_number' => $request->phone_number,
            'OTP'     => $request->OTP,
            'transaction_id' => $request->transaction_id,
        ];

        $customer = $this->customerRepository->checkPhoneEndUser($params['phone_number']);
        if (empty($customer)) {
            $data = [
                'message' => trans('message.txt_not_found_please_create_new_customer', ['attribute' => trans('message.phone_number')]),
                'error_code' => config('constants.error_code.customer_not_found_phoneotp')
            ];
            return CustomerPhoneErrorResource::make($data)->response();
        }

        $transactionId =$this->transactionRepository->getTranById($params['transaction_id']);
        if (empty($transactionId) || $transactionId->status != Transaction::STATUS_NEW) {
            return $this->responseErrorCode(
                trans('message.txt_invalid_transaction'),
                config('constants.error_code.transaction_invalid_phonevalidate'),
                Response::HTTP_NOT_FOUND
            );
        }

        $ip = $request->ip();
        $limit = $this->configRepository->getConfigByEntity('otp', 'limit');
        $limitVal = $limit ? $limit->value : 5;
        $otpTracking = $this->otpTrackingRepository->getOtpTrackingByCustomerId($customer->id, $ip);
        if ($params['OTP'] != $otpTracking->active_code) {
                $unblockAfter = $this->configRepository->getConfigByEntity('otp', 'unblock_after');
                $unblockAfterVal = $unblockAfter ? $unblockAfter->value : 24;
                $time = $otpTracking->times;
                $time++;
                $this->otpTrackingRepository->updateByTimes($time, $customer->id, $ip);
                if ($limitVal == $time) {
                    return response()->json([
                        'status' => 'FAIL',
                        'message' => [trans('message.txt_locked_account')],
                        'time_blocked' => Carbon::now(),
                        'unblock_at' => Carbon::now()->addHours($unblockAfterVal),
                        'error_code' => config('constants.error_code.account_locked_phonevalidate'),
                    ], Response::HTTP_FORBIDDEN);
                }
            $msgError = trans('message.txt_otp_verified_failure');
            $data = [
                'message' => $msgError,
                'number_curr' => $limitVal - $time,
                'total_number_mistakes_allowed' =>  $limitVal-1,
                'error_code' => config('constants.error_code.failed_OTP_phonevalidate')
            ];
            return CodeErrorResource::make($data)->response();
        }

        $timeActivatedOtp = Carbon::parse($otpTracking->activated_at);
        $expiredAfter = $this->configRepository->getConfigByEntity('otp', 'expired_after');
        $expiredAfterValue = $expiredAfter ? $expiredAfter->value : 180;
        $timeLifeOtp = $timeActivatedOtp->addSeconds($expiredAfterValue);
        if ($timeLifeOtp < Carbon::now()) {
            return $this->responseErrorCode(
                trans('message.txt_otp_expired'),
                config('constants.error_code.OTP_expired_phonevalidate'),
                Response::HTTP_REQUEST_TIMEOUT
            );
        }

        $totalValue = 0;
        $redeemFail = false;
        $errorArr = [];
        $successArr = [];
        $transactionItems = $this->transactionRepository->findByFieldTransItem('transaction_id', $params['transaction_id']);
        if (!count($transactionItems)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction_item')]),
                config('constants.error_code.transaction_item_not_found_phonevalidate'),
                Response::HTTP_NOT_FOUND
            );
        }
        foreach ($transactionItems as $transactionItem) {
            $codeDetail = $this->codeRepository->find($transactionItem->code_id);
            $arrCodeId [] = $codeDetail->id;
            if (empty($codeDetail)) {
                $errorArr[] = [
                    'code' => $codeDetail->code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
            } else {
                if ($codeDetail->status == Code::STATUS_NEW) {
                    $successArr[] = [
                        'code' => $codeDetail->code,
                        'value' => $codeDetail->value
                    ];      
                    // $totalValue += $codeDetail->value;
                } else {
                    $errorArr[] = [
                        'code' => $codeDetail->code,
                        'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                    ];
                }
            }
        }

        $redeemAllow = null;
        $limitedCount = 0;
        $CampaignRepo = new CampaignRepository();
        $campaign = $CampaignRepo->findByField('code',$request->header('campaign'))->first();
        $codeGroup = $this->codeRepository->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $this->topUpRepository->validQuota($campaign->id, $key, count($codeValue), true);
            if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
                $limitedCount += count($codeValue);
            } 
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                array_splice($codeValue, 0, $redeemAllow);
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow == 0)) {
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }

        if ($limitedCount == count($transactionItems)) {
            // show error limit redeem
            return $this->responseError(
                trans('message.txt_out_of_quota')
            );
        }
        $totalValue = 0;
        foreach ($successArr as $key => $value) {
            $totalValue += $value['value'];
        }
    
        $successCodeArr = array_column($successArr, 'code');
        if (count($successCodeArr)) {
            $topUp = $this->topUpRepository->doTopUp($params['transaction_id']);
            if (!$topUp) {
                $redeemFail = true;
            }
            if ($redeemFail) {
                $data = [
                    'message' => trans('message.txt_top_up_failure'),
                    'transaction_id' => $params['transaction_id'],
                    'phone_number' => $params['phone_number'],
                    'error_code' => config('constants.error_code.failed_top_up_phonevalidate'),
                ];
                return CustomerTopupErrorResource::make($data)->response();
            } else {
                $data = [
                    'message' => trans('message.txt_top_up_successfully'),
                    'transaction_id' => $params['transaction_id'],
                    'phone_number' => $params['phone_number'],
                    'total_money' => $totalValue,
                    'success_array' => $successArr,
                    'error_array' => $errorArr,
                ];

                if (!empty($otpTracking)) {
                    $this->otpTrackingRepository->updateByTimes($time = 0, $customer->id, $ip);
                }
                return CustomerTopUpSuccessResource::make($data)->response();
            }
        } else {
            return $this->responseErrorCode(
                trans('message.txt_top_up_failure'),
                config('constants.error_code.failed_top_up_phonevalidate')
            );
        }
    }
}
