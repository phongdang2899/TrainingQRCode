<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignGenerateRequest;
use App\Http\Requests\CampaignPostRequest;
use App\Http\Requests\CampaignPatchRequest;
use App\Http\Requests\CampaignDeleteRequest;
use App\Http\Requests\CampaignPutRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\CampaignResource;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\RewardRepository;
use App\Repositories\UserRepository;
use App\Services\CodeService;
use App\Traits\PaginationTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    use PaginationTrait;

    protected $campaignRepository;
    protected $codeRepository;

    public function __construct(
        CampaignRepository $campaignRepository,
        CodeRepository $codeRepository
    )
    {
        $this->campaignRepository = $campaignRepository;
        $this->codeRepository = $codeRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/campaigns",
     *      operationId="getCampaignsList",
     *      tags={"Campaigns"},
     *      summary="Get list of campaigns",
     *      description="Returns list of campaigns",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="search",
     *          in="query",
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="per_page",
     *          in="query",
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="page",
     *          in="query",
     *      @OA\Schema(
     *           type="integer"
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
     *      )
     *     )
     */

    /**
     * List campaigns
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page' => $request->per_page ?? config('constants.pagination.per_page'),
            'page' => $request->page ?? config('constants.pagination.current_page'),
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null
        ];

        $data = $this->campaignRepository->getCampaignResource($params);
        return CampaignResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/campaigns/{id}",
     *      operationId="getCampaignById",
     *      tags={"Campaigns"},
     *      summary="Get campaign information",
     *      description="Returns campaign data",
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
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    /**
     * Show campaign by id
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $campaign = $this->campaignRepository->find($id);
        if (empty($campaign)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.campaign')]),
                config('constants.error_code.campaign_not_found_getcampaigns'),
                Response::HTTP_NOT_FOUND
            );
        };

        return CampaignResource::make($campaign)->response();
    }

    /**
     * @OA\Post(
     *      path="/admin/campaigns",
     *      operationId="storeCampaign",
     *      tags={"Campaigns"},
     *      summary="Store new campaign",
     *      description="Returns campaign data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="name",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start_date",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="end_date",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    /**
     * Create campaign
     *
     * @param CampaignPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CampaignPostRequest $request)
    {
        try {
            $params = [
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'reward_url' => $request->reward_url
            ];
            $params['code'] = CodeService::getNamespace($params['name'] . Carbon::now()->toString());
            $checkCode = $this->campaignRepository->findByField('code', $params['code']);
            if ($checkCode->count()) {
                $params['code'] = CodeService::getNamespace($params['name'] . Carbon::now()->toString() . Str::random(4));
            }
            $params['created_by'] = Auth::id();
            $campaignCreated = $this->campaignRepository->create($params);
            if (empty($campaignCreated)) {
                return $this->responseErrorCode(
                    trans('message.txt_created_failure', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.save_campaign_failed_postcampaigns'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return CampaignResource::make($campaignCreated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.campaign')]),
                config('constants.error_code.save_campaign_failed_postcampaigns'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Put(
     *      path="/admin/campaigns/{id}",
     *      operationId="updateCampaignById",
     *      tags={"Campaigns"},
     *      summary="Update campaign",
     *      description="Returns campaign data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Campaign id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="name",
     *          in="query",
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start_date",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="end_date",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    /**
     * Update campaign
     *
     * @param CampaignPutRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CampaignPutRequest $request, int $id)
    {
        try {
            $params = [
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status,
                'reward_url' => $request->reward_url
            ];
            $campaign = $this->campaignRepository->find($id);
            if (empty($campaign)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.campaign_not_found_putcampaigns'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $params['updated_by'] = Auth::id();
            $campaignUpdated = $this->campaignRepository->update($id, $params);
            if (!$campaignUpdated) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.update_campaign_failed_putcampaigns'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return CampaignResource::make($campaignUpdated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(
                trans('message.txt_updated_failure', ['attribute' => trans('message.campaign')]),
                config('constants.error_code.update_campaign_failed_putcampaigns'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Patch(
     *      path="/admin/campaigns/{id}/status",
     *      operationId="updateStatusCampaignById",
     *      tags={"Campaigns"},
     *      summary="Update status campaign",
     *      description="Returns campaign data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Campaign id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    /**
     * Change status campaign
     *
     * @param CampaignPatchRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(CampaignPatchRequest $request, int $id)
    {
        try {
            $params = [
                'status' => $request->status
            ];
            $campaign = $this->campaignRepository->find($id);
            if (empty($campaign)) {
                return $this->responseError(
                    trans('message.txt_not_found', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.campaign_not_found_patchcampaigns'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $params['updated_by'] = Auth::id();
            $statusUpdated = $this->campaignRepository->update($id, $params);
            if (!$statusUpdated) {
                return $this->responseError(
                    trans('message.txt_updated_status_failure', ['attribute' => trans('message.campaign')]),
                    config('constants.error_code.update_status_campaign_failed_patchcampaigns'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return CampaignResource::make($statusUpdated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(
                trans('message.txt_updated_status_failure', ['attribute' => trans('message.campaign')]),
                config('constants.error_code.update_status_campaign_failed_patchcampaigns'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Delete(
     *      path="/admin/campaigns",
     *      operationId="deleteCampaign",
     *      tags={"Campaigns"},
     *      summary="Delete existing campaign",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Campaign id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer")
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
     * Delete campaign
     *
     * @param CampaignDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(CampaignDeleteRequest $request)
    {
        try {
            $params = [
                'ids' => $request->ids
            ];
            foreach ($request->ids as $id) {
                $campaign = $this->campaignRepository->find($id);
                if (empty($campaign)) {
                    return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.campaign')]), 404);
                }
                $checkCodeExist = $this->campaignRepository->checkCodeExist($id);
                if (!empty($checkCodeExist)) {
                    return $this->responseError(trans('message.txt_deleted_failure_campaign_has_code'));
                }
                $input['deleted_by'] = Auth::id();
                $this->campaignRepository->update($id, $input);
            }
            $campaignDeleted = $this->campaignRepository->destroyArr($params['ids']);
            if (!$campaignDeleted) {
                return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.campaign')]));
            }
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.campaign')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.campaign')]));
        }
    }

    /**
     * Create campaign
     *
     * @param CampaignGenerateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateCode(CampaignGenerateRequest $request)
    {
        try {
            $input = [
                'campaign_id' => $request->campaign_id
            ];
            $campaign = $this->campaignRepository->find($input['campaign_id']);
            if (!$campaign) {
                return $this->responseError('Action not allowed');
            }
            $rewardRepo = new RewardRepository;
            $codeData = $rewardRepo->createRewardCode($campaign->id, (new UserRepository())->getUserIdDefault());
            if (!$codeData) {
                return $this->responseError('Can\'t generate code. Campaign data not correct');
            }
            return $this->responseSuccess(['qty' => $codeData]);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_created_failure', ['attribute' => trans('message.campaign')]));
        }
    }
}
