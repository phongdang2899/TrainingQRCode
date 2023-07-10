<?php

namespace App\Http\Controllers\Api\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckCodeRequest;
use App\Models\Code;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\TopUpRepository;
use App\Repositories\WatcherRepository;
use Carbon\Carbon;
use Illuminate\Http\Response;

class GiftCodeController extends Controller
{
    protected $codeRepository;
    protected $campaignRepository;
    protected $watcherRepository;

    public function __construct(
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository,
        WatcherRepository $watcherRepository
    ) {
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
        $this->watcherRepository = $watcherRepository;
    }

    /**
     * @OA\Post(
     *      path="/enduser/giftcode/check",
     *      operationId="checkCode",
     *      tags={"EndUsers"},
     *      summary="Check code is valid",
     *      description="Check code is valid and returns no content",
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
     * Check Code isValid
     *
     * @param CheckCodeRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCode(CheckCodeRequest $request)
    {
        $params = [
            'codes' => array_unique($request->codes)
        ];
        $successArr = [];
        $errorArr = [];
        $arrCodeId = [];
        $ip = $request->ip();
        $campaign = $this->campaignRepository->findByField('code', $request->header('campaign'))->first();
        foreach ($params['codes'] as $code) {
            $codeDetails = $this->codeRepository->getCodeByCodeAndCampaign($code, $campaign->id);
            if (empty($codeDetails)) {
                $errorArr[] = [
                    'code' => $code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
            } else {
                $arrCodeId[] = $codeDetails->id;
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
        $redeemAllow = null;
        $limitedCount = 0;
        $isOutOfQuota = false;
        $topupRepo = new TopUpRepository();
        $codeGroup = $this->codeRepository->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $topupRepo->validQuota($campaign->id, $key, count($codeValue), true);
            // if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
            //     $isOutOfQuota = true;
            //     $limitedCount += count($codeValue);
            //     $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            // }
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                $isOutOfQuota = true;
                array_splice($codeValue, 0, $redeemAllow);
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                $isOutOfQuota = true;
                $limitedCount += count($codeValue);
                $this->codeRepository->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }

        if ($limitedCount == count($params['codes'])) {
            // show error limit redeem
            // return $this->responseError(
            //     trans('message.txt_out_of_quota')
            // );
            return [
                'data' => $errorArr,
                'failedMsd' => trans('message.txt_out_of_quota')
            ];
        }
        $watcher = $this->watcherRepository->findByField('ip', $ip)->first();
        if (count($errorArr) == count($params['codes']) && !$isOutOfQuota) {
            $configRepository = new ConfigRepository;
            $limit = $configRepository->getConfigByEntity('failed_ip', 'limit');
            $limitVal = $limit ? $limit->value : 5;
            if (!$watcher) {
                $time = 1;
                $this->watcherRepository->createByField($ip, 1, null, 0);
            } else {
                $time = (int)$watcher->times + 1;
                if ($time >= $limitVal) {
                    $this->watcherRepository->updateByField($time, Carbon::now(), (int)$watcher->total_times + 1, null, $ip);
                } else {
                    $this->watcherRepository->updateByTimes($time, null, $ip);
                }
            }
            $timesLeft = max($limitVal - $time, 0);
            if ($timesLeft == 0) {
                return response()->json([
                    'status' => 'FAIL',
                    'message' => [trans('message.txt_ip_is_blocked')]
                ], Response::HTTP_FORBIDDEN);
            }
            $msgError = trans('message.txt_warning_error_code') . $timesLeft . trans('message.txt_turn');
            return [
                'data' => $errorArr,
                'failedMsg' => $msgError,
                'current_number' => $timesLeft
            ];
        }
        if ($watcher != null) {
            $this->watcherRepository->updateByTimes(0, null, $ip);
        }
        $codeArr = array_merge($successArr, $errorArr);
        return ['data' => $codeArr];
    }
}
