<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignConfigPostRequest;
use App\Http\Requests\SearchPostRequest;
use App\Http\Resources\CampaignConfigResource;
use App\Http\Resources\CampaignResource;
use App\Repositories\CodeRepository;
use App\Repositories\UserRepository;
use App\Traits\PaginationTrait;
use App\Traits\UserPermissionTrait;
use App\Repositories\CampaignConfigRepository;
use App\Repositories\CampaignRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CampaignConfigController extends Controller
{
    use UserPermissionTrait, PaginationTrait;

    protected $campaignConfigRepository;
    protected $campaignRepository;

    public function __construct(
        CampaignConfigRepository $campaignConfigRepository,
        CampaignRepository $campaignRepository
    ){
        $this->campaignConfigRepository = $campaignConfigRepository;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/campaigns/{id}/config",
     *      operationId="getCampaignConfigsList",
     *      tags={"Campaigns"},
     *      summary="Get list of campaign configs by campaign id",
     *      description="Returns data campaign configs",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Campaign id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *      @OA\Schema(
     *          type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Success",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *          ),
     *      )
     **/
    /**
     *
     * @param SearchPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(SearchPostRequest $request, $campaign_id)
    {
        $params = [
            'search' => $request->search ?? ''
        ];
        $campaign = $this->campaignRepository->find($campaign_id);
        if (empty($campaign)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.campaign')]),
                config('constants.error_code.campaign_not_found_campaignconfig'),
                Response::HTTP_NOT_FOUND
            );
        };
        $data = $this->campaignConfigRepository->getCampaignConfigResource($params, $campaign_id);
        return CampaignConfigResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Post(
     * path="/admin/campaigns/{id}/config",
     *   tags={"Campaigns"},
     *   summary="Create or update campaign config",
     *   description="Returns data campaign config",
     *   operationId="CreateOrUpdateCampaignConfig ",
     *   security = {{"Bearer":{}}},
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="campaign id",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *       name="config[]",
     *       description="config of campaign",
     *       required=true,
     *       in="query",
     *       @OA\Schema(
     *          type="array",
     *          @OA\Items(type="object")
     *         )
     *    ),
     *   @OA\Parameter(
     *      name="status",
     *      in="query",
     *      required=false,
     *      description="status of campaign config",
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *)
     **/
    /**
     *
     * @param CampaignConfigPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CampaignConfigPostRequest $request, int $id)
    {
        try {
            $campaign = $this->campaignRepository->find($id);
            if (empty($campaign)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.campaign_not_found_putcampaigns'),
                    Response::HTTP_NOT_FOUND
                );
            }
            // $codeRepo = new CodeRepository();
            // $code = $codeRepo->findByField('campaign_id', $campaign->id)->first();
            // if ($code) {
            //     return $this->responseErrorCode(
            //         'Action not allowed',
            //         config('constants.error_code.campaign_not_found_putcampaigns')
            //     );
            // }

            $params = $request->config;
            $dataMap = [];
            $quotaMap = [];
            $codeRepo = new CodeRepository();
            $code = $codeRepo->findByField('campaign_id', $campaign->id)->first();
            $userId = (new UserRepository())->getUserIdDefault();
            foreach ($params as $item) {
                if (!isset($item['type'])) {
                    return $this->responseErrorCode(
                        trans('message.txt_created_failure', ['attribute' => trans('message.campaign_config')]),
                        config('constants.error_code.create_campaign_config_failed_campaignconfig')
                    );
                }

                $type = (int)$item['type'];
                $checkType = $this->campaignConfigRepository->checkTypeByCampaignId($campaign->id, $type);
                if ($code && !$checkType ) {
                    return $this->responseErrorCode(
                        'Action not allowed',
                        config('constants.error_code.campaign_not_found_putcampaigns')
                    );
                }

                $countParams = count(array_column($params, 'type'));
                $countParamsWithUnique = count(array_unique(array_column($params, 'type')));
                if($countParams != $countParamsWithUnique) {
                    return $this->responseErrorCode(
                        trans('message.txt_created_failure', ['attribute' => trans('message.campaign_config')]),
                        config('constants.error_code.create_campaign_config_failed_campaignconfig')
                    );
                }

                if ($type && isset($item['value']) && !isset($item['quota'])) {
                    $dataMap[$type] = [
                        'campaign_id' => $campaign->id,
                        'type' => $type,
                        'value' => (int)$item['value'],
                        'created_by' => $userId
                    ];
                }

                if ($type && isset($item['value']) && isset($item['quota'])) {
                    $quotaMap[] = [
                        'campaign_id' => $campaign->id,
                        'quota' => (int)$item['quota'],
                        'type' => $type,
                        'value' => (int)$item['value'],
                        'created_by' => $userId
                    ];
                }
            }
            // if (!$dataMap) {
            //     return $this->responseErrorCode(
            //         'Data format wrong',
            //         config('constants.error_code.campaign_not_found_putcampaigns')
            //     );
            // }
            $hasAction = false;
            $hasQuota = false;
            $campaignConfig = $this->campaignConfigRepository->findByField('campaign_id', $campaign->id);
            $arDelete = [];
            foreach ($campaignConfig as $item) {
                if (!in_array($item->type, array_keys($dataMap))) {
                    $arDelete[] = $item->id;
                } else {
                    $hasAction = $this->campaignConfigRepository->update($item->id, [
                        'value' => $dataMap[$item->type]['value'],
                        'quota' => null,
                        'updated_by' => $userId
                    ]);
                    unset($dataMap[$item->type]);
                }
            }
            if ($arDelete) {
                $hasAction = $this->campaignConfigRepository->destroyArr($arDelete);
            }
            if ($dataMap) {
                $hasAction = $this->campaignConfigRepository->saveMultiple(array_values($dataMap));
            }
            if ($quotaMap) {
                $hasQuota = $this->campaignConfigRepository->saveMultipleWithQuota(array_values($quotaMap));
            }
            if ($hasAction) {
                return CampaignResource::make($campaign)->response();
            }
            if ($hasQuota) {
                return CampaignResource::make($campaign)->response();
            }
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.campaign_config')]),
                config('constants.error_code.create_campaign_config_failed_campaignconfig'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.campaign_config')]),
                config('constants.error_code.create_campaign_config_failed_campaignconfig'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
