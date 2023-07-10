<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfigPutRequest;
use App\Http\Requests\SearchPostRequest;
use App\Http\Resources\ConfigResource;
use App\Models\Campaign;
use App\Models\Code;
use App\Models\Config;
use App\Models\Customer;
use App\Models\User;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Traits\UserPermissionTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{
    use UserPermissionTrait;
    protected $configRepository;
    protected $campaignRepository;
    protected $codeRepository;

    public function __construct(
        ConfigRepository $configRepository,
        CampaignRepository $campaignRepository,
        CodeRepository $codeRepository
    ) {
        $this->configRepository = $configRepository;
        $this->campaignRepository = $campaignRepository;
        $this->codeRepository = $codeRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/configs",
     *      operationId="getConfigsList",
     *      tags={"Configs"},
     *      summary="Get list of configs",
     *      description="Returns list of configs",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="search",
     *      in="query",
     *    @OA\Schema(
     *      type="string"
     *          )
     *      ),
     *   @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *  @OA\Response(
     *      response=403,
     *      description="Forbidden"
     *      )
     *)
     **/
    /**
     *
     * @param SearchPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(SearchPostRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
        ];

        $configByEntityId =$this->configRepository->getConfigResource($params);
        $data = [];
        foreach ($configByEntityId as $entityId) {
            $configs = $this->configRepository->getConfigByEntityId($entityId['entity_id']);
            $ar = [];
            foreach ($configs as $config) {
                $arr = [ $config->entity_type=> $config->value];
                $ar[] = $arr;
                $data[$entityId['entity_id']] = $ar;
            }
        }

        return ConfigResource::make($data);
    }

    /**
     * @OA\Get(
     *      path="/admin/configs/status",
     *      operationId="getStatusList",
     *      tags={"Configs"},
     *      summary="Get list status",
     *      description="Returns list status",
     *      security={{"Bearer":{}}},
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
     * List status
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        $statusConfigs = [
            Config::STATUS_ACTIVE => 'Hoạt động',
            Config::STATUS_INACTIVE => 'Ngưng hoạt động'
        ];

        $statusCampaigns = [
            Campaign::STATUS_ACTIVE => 'Hoạt động',
            Campaign::STATUS_DRAFT => 'Nháp',
            Campaign::STATUS_INACTIVE => 'Ngưng hoạt động'
        ];

        $statusCodes = [
            Code::STATUS_LOCKED => 'Khóa',
            Code::STATUS_NEW => 'Mới',
        ];

        $statusUsers = [
            User::STATUS_LOCKED => 'Khóa',
            User::STATUS_ACTIVE => 'Hoạt động'
        ];

        $statusCustomers = [
            Customer::STATUS_ACTIVE => 'Hoạt động',
            Customer::STATUS_INACTIVE => 'Không hoạt động'
        ];

        $data = [
            'campaigns' => $statusCampaigns,
            'codes' => $statusCodes,
            'configs' => $statusConfigs,
            'users' => $statusUsers,
            'customers' => $statusCustomers
        ];

        return $this->responseSuccess($data);
    }

    /**
     * @OA\Put(
     * path="/admin/configs/{id}",
     *   tags={"Configs"},
     *   summary="update config",
     *   description="Returns config",
     *   operationId="UpdateConfig ",
     *   security = {{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="id of config",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="entity_id",
     *      in="query",
     *      description="entity id of campaign",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="entity_type",
     *      in="query",
     *      description=" entity type of config",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="value",
     *      in="query",
     *      description="value of config",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="status of campaign config",
     *      required=true,
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
     *   @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *)
     **/
    /**
     *
     * @param ConfigPutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ConfigPutRequest $request, $entityId)
    {
        try {
            foreach ($request->config as $key) {
                foreach ($key as $type => $value) {
                    $config = $this->configRepository->getConfigByEntity($entityId, $type);
                    if($config){
                       $configUpdated = $this->configRepository->updateValue($entityId, $value, $type);
                    }
                    if(empty($configUpdated)){
                        return $this->responseErrorCode(
                            trans('message.txt_updated_failure', ['attribute' => trans('message.config')]),
                            config('constants.error_code.update_config_failed_putconfig'),
                            Response::HTTP_UNPROCESSABLE_ENTITY
                        );
                   }
                }
            }
            return $this->responseSuccess(trans('message.txt_updated_status_successfully', ['attribute' => trans('message.config')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_failure', ['attribute' => trans('message.config')]),
                config('constants.error_code.update_config_failed_putconfig'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
