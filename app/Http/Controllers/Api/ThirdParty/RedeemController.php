<?php

namespace App\Http\Controllers\Api\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Requests\RedeemCodePostRequest;
use App\Http\Requests\RedeemThirdpartyRequest;
use App\Http\Resources\CodeErrorResource;
use App\Http\Resources\CustomerPhoneErrorResource;
use App\Models\Code;
use App\Models\Customer;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OtpTrackingRepository;
use App\Repositories\TopUpRepository;
use App\Repositories\TransactionRepository;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Http\Response;

class RedeemController extends Controller
{
    /**
     * @OA\Post(
     *      path="/3rd/redeem",
     *      operationId="redeem",
     *      tags={"ThirdParty"},
     *      summary="Redeem call by zalo",
     *      description="Check phone and otp is valid to redeem",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *             type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                   example={
     *                    "status": "OK",
     *                    "transaction_id": "03f51deb-1082-4616-876c-94262c5863fb",
     *                    "message": "Nhận thưởng thành công",
     *                    "phone": "0955555555",
     *                    "total_money" : "400000",
     *                    "success_array" :{
     *                          {"code": "de441612556f5e74a52d844263bf1d90",
     *                          "value" : "20000"}
     *                      },
     *                     "error_array" :{
     *                          {"code": "de441612556f5e74a52d844263bf1d90",
     *                          "value" : "40000"}
     *                      },
     *                   },
     *                 )
     *             )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated."
     *                     }
     *                 )
     *             )
     *
     *      ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status" : "FAIL",
     *                   "message" : "Tài khoản của bạn đang bị khóa",
     *                   "time_blocked" : "2020-02-08 15:15:32",
     *                   "unblock_at" : "2020-03-08 15:15:32",
     *                   "error_code" : "114",
     *                     },
     *                 )
     *             ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status": "FAIL",
     *                   "message": "chiến dịch không hợp lệ!"
     *                     },
     *                 )
     *             )
     *      ),
     *     @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "message": "Dữ liệu đưa vào không hợp lệ.",
     *                       "errors": {
     *                       "otp": {
     *                               "Định dạng không hợp lệ!"
     *                               },
     *                       "phone": {
     *                               "Định dạng không hợp lệ!"
     *                              },
     *                     },
     *                  },
     *                 )
     *            )
     *      ),
     * )
     */

    /**
     * Check phone, otp and redeem by third party
     *
     * @param RedeemThirdpartyRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReward(RedeemThirdpartyRequest $request)
    {
        $params = [
            'phone' => $request->phone,
            'otp' => $request->otp,
        ];
        $errorArr = [];
        $successArr = [];
        $totalValue = 0;
        $customerRepo = new CustomerRepository();
        $transactionRepo = new TransactionRepository();
        $configRepo = new ConfigRepository();
        $otpTrackingRepo = new OtpTrackingRepository();
        $ip = $request->ip();
        $limit = $configRepo->getConfigByEntity('otp', 'limit');
        $limitVal = $limit ? $limit->value : 5;

        $customer = $customerRepo->checkPhoneEndUser($params['phone']);
        if (empty($customer)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_postredeem'),
                Response::HTTP_NOT_FOUND
            );
        }
        $otpTracking = $otpTrackingRepo->getOtpTrackingByCustomerId($customer->id, $ip);
        if (empty($otpTracking)) {
            return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.otp_tracking')]), Response::HTTP_NOT_FOUND);
        };
        $transaction = $transactionRepo->find($otpTracking->transaction_id);
        if (empty($transaction)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction')]),
                config('constants.error_code.transaction_not_found_postredeem'),
                Response::HTTP_NOT_FOUND
            );
        }

        if ($params['otp'] != $otpTracking->active_code) {
            $unblockAfter = $configRepo->getConfigByEntity('otp', 'unblock_after');
            $unblockAfterVal = $unblockAfter ? $unblockAfter->value : 24;
            $time = $otpTracking->times + 1;
            $otpTrackingRepo->updateByTimes($time, $customer->id, $ip);
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
                'total_number_mistakes_allowed' => $limitVal,
                'error_code' => config('constants.error_code.failed_OTP_phonevalidate')
            ];
            return CodeErrorResource::make($data)->response();
        }
        $timeActivatedOtp = Carbon::parse($otpTracking->activated_at);
        $expiredAfter = $configRepo->getConfigByEntity('otp', 'expired_after');
        $expiredAfterValue = $expiredAfter ? $expiredAfter->value : 180;
        $timeLifeOtp = $timeActivatedOtp->addSeconds($expiredAfterValue);
        if ($timeLifeOtp < Carbon::now()) {
            return $this->responseErrorCode(
                trans('message.txt_otp_expired'),
                config('constants.error_code.OTP_expired_phonevalidate'),
                Response::HTTP_REQUEST_TIMEOUT
            );
        }
        $codeRepo = new CodeRepository();
        $transactionItems = $transactionRepo->findByFieldTransItem('transaction_id', $otpTracking->transaction_id);
        if (!count($transactionItems)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction_item')]),
                config('constants.error_code.transaction_item_not_found_phonevalidate'),
                Response::HTTP_NOT_FOUND
            );
        }
        $arrCodeId = [];
        foreach ($transactionItems as $transactionItem) {
            $codeDetail = $codeRepo->find($transactionItem->code_id);
            if (empty($codeDetail)) {
                $errorArr[] = [
                    'code' => $codeDetail->code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
            } else {
                $arrCodeId[] = $codeDetail->id;
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
        $topupRepo = new TopUpRepository();
        $codeRepo = new CodeRepository();
        $codeData = $codeRepo->find($transactionItems[0]->code_id);
        $codeGroup = $codeRepo->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $topupRepo->validQuota($codeData->campaign_id, $key, count($codeValue), true);
            // if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
            //     $limitedCount += count($codeValue);
            // }
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                array_splice($codeValue, 0, $redeemAllow);
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                $limitedCount += count($codeValue);
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }
        foreach ($successArr as $v) {
            $totalValue += $v['value'];
        }
        if ($limitedCount == count($transactionItems)) {
            // show error limit redeem
            return $this->responseError(
                trans('message.txt_out_of_quota')
            );
        }
        $topupRepo = new TopUpRepository();
        $callTopup = $topupRepo->doTopUp($otpTracking->transaction_id);
        if (!$callTopup) {
            return $this->responseErrorCode(
                trans('message.txt_top_up_failure'),
                config('constants.error_code.failed_top_up_postredeem')
            );
        }
        if (!empty($otpTracking)) {
            (new OtpTrackingRepository())->updateByTimes($time = 0, $customer->id, $ip);
        }
        return $this->responseSuccess([
            'transaction_id' => $otpTracking->transaction_id,
            'message' => trans('message.txt_top_up_successfully'),
            'phone' => $params['phone'],
            'total_money' => $totalValue,
            'success_array' => $successArr,
            'error_array' => $errorArr,
        ]);
    }
    /**
     * @OA\Post(
     *      path="/3rd/nap-the",
     *      operationId="checkAndCreateTransaction",
     *      tags={"ThirdParty"},
     *      summary="Create transaction call by zalo",
     *      description="Check phone and codes array is valid to create transaction and send otp",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          in="query",
     *          required=true,
     *          @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="codes[]",
     *          description="array codes",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *             type="array",
     *             @OA\Items(type="string")
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                   example={
     *                    "status": "OK",
     *                    "message": "Tạo giao dịch thành công!",
     *                    "transaction_id": "03f51deb-1082-4616-876c-94262c5863fb",
     *                    "phone": "0955555555",
     *                    "success_array" :{
     *                          {"code": "de441612556f5e74a52d844263bf1d90",
     *                          "value" : "20000"}
     *                      },
     *                     "error_array" :{
     *                          {"code": "de441612556f5e74a52d844263bf1d90",
     *                          "value" : "40000"}
     *                      },
     *                   },
     *                 )
     *             )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated."
     *                     }
     *                 )
     *             )
     *
     *      ),
     *     @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status" : "FAIL",
     *                   "message" : "Tài khoản của bạn đang bị khóa",
     *                   "time_blocked" : "2020-02-08 15:15:32",
     *                   "unblock_at" : "2020-03-08 15:15:32",
     *                   "error_code" : "114",
     *                     },
     *                 )
     *             ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Not Found",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status": "FAIL",
     *                   "message": "chiến dịch không hợp lệ!"
     *                     },
     *                 )
     *             )
     *      ),
     *     @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "message": "Dữ liệu đưa vào không hợp lệ.",
     *                       "errors": {
     *                       "phone": {
     *                               "Định dạng không hợp lệ!"
     *                               },
     *                       "code": {
     *                               "Định dạng không hợp lệ!"
     *                              },
     *                     },
     *                  },
     *                 )
     *            )
     *      ),
     * )
     */

    /**
     * Check and create transaction by third party
     *
     * @param RedeemCodePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkAndCreateTransaction(RedeemCodePostRequest $request)
    {
        $params = [
            'phone' => $request->phone,
            'codes' => array_unique($request->codes),
        ];

        $campaignRepo = new CampaignRepository();
        $campaign = $campaignRepo->findByField('code', $request->header('campaign'))->first();
        if (!$campaign) {
            return response()->json([
                'status' => 'FAIL',
                'message' => [trans('message.txt_not_found', ['attribute' => trans('message.campaign')])],
                'error_code' => config('constants.error_code.campaign_not_found_postnapthe')
            ], Response::HTTP_NOT_FOUND);
        }

        $errorArr = [];
        $successArr = [];
        $codeRepo = new CodeRepository();
        $arrCodeId = [];
        foreach ($params['codes'] as $code) {
            $codeDetail = $codeRepo->getByCode($code);
            if (empty($codeDetail)) {
                $errorArr[] = [
                    'code' => $code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
                continue;
            }
            $arrCodeId[] = $codeDetail->id;
            if ($codeDetail->status === Code::STATUS_NEW) {
                $successArr[] = [
                    'code' => $codeDetail->code,
                    'value' => $codeDetail->value
                ];
            } else {
                $errorArr[] = [
                    'code' => $codeDetail->code,
                    'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                ];
            }
        }
        $redeemAllow = null;
        $limitedCount = 0;
        $topupRepo = new TopUpRepository();
        $codeRepo = new CodeRepository();
        $codeGroup = $codeRepo->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $topupRepo->validQuota($campaign->id, $key, count($codeValue), true);
            // if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
            //     $isOutOfQuota = true;
            //     $limitedCount += count($codeValue);
            //     $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            // }
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                array_splice($codeValue, 0, $redeemAllow);
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                $limitedCount += count($codeValue);
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }

        if ($limitedCount == count($params['codes'])) {
            return [
                'data' => $errorArr,
                'failedMsd' => trans('message.txt_out_of_quota')
            ];
        }
        $successCodeArr = array_column($successArr, 'code');
        if (!count($successCodeArr)) {
            return $this->responseErrorCode(
                trans('message.txt_invalid', ['attribute' => trans('message.code')]),
                config('constants.error_code.3rd_code_invalid_phoneotp')
            );
        }

        $customerRepo = new CustomerRepository();
        $customer = $customerRepo->checkPhoneEndUser($params['phone'], false);
        if (empty($customer)) {
            $codeStr = implode(",", $successCodeArr);
            $phoneParams = '?phone=' . $params['phone'];
            $linkToDirect = rtrim($campaign->reward_url, "/") . '/' . $codeStr . $phoneParams;
            $data = [
                'message' => trans('message.txt_not_found_please_create_new_customer', ['attribute' => trans('message.phone_number')]),
                'error_code' => config('constants.error_code.customer_not_found_postredeem'),
                'link' => $linkToDirect
            ];
            return new CustomerPhoneErrorResource($data);
        }

        switch ($customer->status) {
            case Customer::STATUS_PENDING:
                $data = [
                    'message' => trans('message.txt_customer_pending'),
                    'error_code' => config('constants.error_code.customer_pending_phonecheck')
                ];
                return new CustomerPhoneErrorResource($data);
                break;
            case Customer::STATUS_INACTIVE:
                $data = [
                    'message' => trans('message.txt_phone_number_blocking'),
                    'error_code' => config('constants.error_code.phone_blocking_phonecheck')
                ];
                return new CustomerPhoneErrorResource($data);
                break;
            default:
                break;
        }
        $ip = $request->ip();
        $transactionId = $this->createTransaction($ip, $params['codes'], $customer->id);
        if (!$transactionId) {
            return $this->responseErrorCode(
                trans('message.txt_created_transaction_failure'),
                config('constants.error_code.create_transaction_failed_createtranbycode')
            );
        }
        $sentOtp = $this->sendOTP($ip, $customer, $transactionId);
        if (!$sentOtp) {
            return $this->responseErrorCode(
                trans('message.txt_send_otp_failure'),
                config('constants.error_code.3rd_send_otp_failed')
            );
        }

        return $this->responseSuccess([
            'message' => trans('message.txt_created_transaction_successfully'),
            'transaction_id' => $transactionId,
            'phone' => $params['phone'],
            'success_array' => $successArr,
            'error_array' => $errorArr,
        ]);
    }

    private function sendOTP($ip, $customer, $transactionId)
    {
        $otpTrackingRepo = new OtpTrackingRepository();
        $otp = rand(1000, 999999);
        $otp = str_pad($otp, 6, '0', STR_PAD_LEFT);
        $otpTracking = $otpTrackingRepo->getOtpTrackingByCustomerId($customer->id, $ip);
        if (empty($otpTracking)) {
            $otpTracking = $otpTrackingRepo->createOtpTracking($customer->id, $ip, $otp, 1);
            if (empty($otpTracking)) {
                return false;
            }
        }
        $smsService = new SMSService();
        $sentSMS = $smsService->sendSMS($customer->phone_number, $otp);
        if (!$sentSMS) {
            return false;
        }
        if ($otpTracking->active_code != $otp) {
            $times = $otpTracking->times + 1;
            $otpOld = $otpTracking->active_code;
            $previousTime = $otpTracking->activated_at;
            $otpTrackingRepo->updateOtpTracking($customer->id, $ip, $otp, $times, $otpOld, $previousTime);
        }
        $updated = $otpTrackingRepo->update($otpTracking->id, ['transaction_id' => $transactionId]);
        if (!$updated) {
            return false;
        }
        return true;
    }

    private function createTransaction($ip, $codes, $customer_id)
    {
        $transaction = new TransactionRepository();
        $transactionCreated = $transaction->createTransaction($ip, $customer_id, 'zalo');
        if (empty($transactionCreated)) {
            return false;
        }
        foreach ($codes as $code) {
            $codes = new CodeRepository();
            $codeDetails = $codes->findByField('code', $code)->first();
            if (empty($codeDetails)) {
                return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.code')]), 404);
            }
            $transaction = new TransactionRepository();
            $transItemCreated = $transaction->createTranItem($transactionCreated->id, $codeDetails->id, $codeDetails->value);
            if (empty($transItemCreated)) {
                return false;
            }
        }
        return $transactionCreated->id;
    }
}
