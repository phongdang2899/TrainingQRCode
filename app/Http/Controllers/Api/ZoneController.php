<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ZoneDeleteRequest;
use App\Http\Requests\ZonePostRequest;
use App\Http\Requests\ZoneProvincesPutRequest;
use App\Http\Resources\ProvinceNameResource;
use App\Http\Resources\ZoneResource;
use App\Models\Zone;
use App\Repositories\ZoneRepository;
use App\Repositories\ZoneProvinceRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ZoneController extends Controller
{
    /**
     * @OA\Get(
     *      path="/admin/zones",
     *      operationId="getZonesList",
     *      tags={"Zones"},
     *      summary="Get list of zones",
     *      description="Returns list of zones",
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
     * List zones
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $zones = new ZoneRepository();
        $data = $zones->getZoneResource();
        return ZoneResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/zones/{id}",
     *      operationId="getZoneById",
     *      tags={"Zones"},
     *      summary="Get zone information",
     *      description="Returns zone data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Zone id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
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
     * Show Zone by id
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $zones = new ZoneRepository();
        $zone = $zones->find($id);
        if (empty($zone)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.zone')]),
                config('constants.error_code.zone_not_found_getzones'),
                Response::HTTP_NOT_FOUND
            );
        };

        return ZoneResource::make($zone)->response();
    }

    /**
     * @OA\Post(
     *      path="/admin/zones",
     *      operationId="storeZone",
     *      tags={"Zones"},
     *      summary="Store new zone",
     *      description="Returns zone data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="name",
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
     * Create zone
     *
     * @param ZonePostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ZonePostRequest $request)
    {
        try {
            $input = $request->only(['name','status','created_by']);
            $input['created_by'] = Auth::id();
            $input['status'] = Zone::STATUS_ACTIVE;
            $zoneRepo = new ZoneRepository();
            $zoneCreated = $zoneRepo->create($input);
            if (empty($zoneCreated)) {
                return $this->responseErrorCode(
                    trans('message.txt_created_failure', ['attribute' => trans('message.zone')]),
                    config('constants.error_code.save_zone_failed_postzones'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            
            return new ZoneResource($zoneCreated);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.zone')]),
                config('constants.error_code.save_zone_failed_postzones'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Put(
     *      path="/admin/zones/{id}/provinces",
     *      operationId="updateProvincesByZoneId",
     *      tags={"Zones"},
     *      summary="Update province id and name zone",
     *      description="Returns zones and provinces data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="zone id",
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
     *          name="province_ids[]",
     *          description="Province id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer")
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
     * Update provinces by zone id 
     *
     * @param ZoneProvincesPutRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ZoneProvincesPutRequest $request, $id)
    {
        $params = [
            'province_ids' => $request->province_ids,
            'name' => $request->name,
        ];
        if($params['name'] == null && $params['province_ids'] == null){
            return $this->responseErrorCode(
                trans('message.txt_updated_failure', ['attribute' => trans('message.zone_province')]),
                config('constants.error_code.update_zone_province_failed_patchzoneprovinces'),
            );
        }
        $zoneRepo = new ZoneRepository();
        $zone = $zoneRepo->find($id);
        if (empty($zone)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.zone')]),
                config('constants.error_code.zone_not_found_patchzones'),
                Response::HTTP_NOT_FOUND
            );
        }
        if($params['name']){
            $zoneUpdated = $zoneRepo->updateName($params['name'], $id);
        }
        if($params['province_ids']){
            $zoneProvincesRepo = new ZoneProvinceRepository();
            $zoneProvince = $zoneProvincesRepo->getProvinceIdByZoneId($id)->toArray();
            $oldProvinceIds = array_column($zoneProvince, 'province_id');
            $newProvinceIds = $params['province_ids'];
            $additionalIds = array_diff($newProvinceIds, $oldProvinceIds);
            foreach ($additionalIds as $key => $value) {
                $input = [
                    'province_id' => $value,
                    'zone_id' => $id
                ];
                $zoneProvincesCreated = $zoneProvincesRepo->create($input);
                if(empty($zoneProvincesCreated)) 
                {
                    return $this->responseErrorCode(
                        trans('message.txt_created_failure', ['attribute' => trans('message.zone_province')]),
                        config('constants.error_code.save_zone_province_failed_postzoneprovince'),
                        Response::HTTP_UNPROCESSABLE_ENTITY
                    );
                }
            }
    
            $deleteIds = array_diff($oldProvinceIds,$newProvinceIds);
            foreach ($deleteIds as $key => $value) {
                $input = [
                    'zone_id' => $id,
                    'province_id' => $value
                ];
                $zoneProvinces = $zoneProvincesRepo->findByZoneAndProvince($value, $id);
                if (empty($zoneProvinces)) {
                    return $this->responseErrorCode(
                        trans('message.txt_not_found', ['attribute' => trans('message.zone_province')]),
                        config('constants.error_code.zone_province_not_found_patchzoneprovinces'),
                        Response::HTTP_NOT_FOUND
                    );
                } else {
                    $zoneProvincesDeleted = $zoneProvincesRepo->deleteZoneProvince($value, $id);
                    if(!$zoneProvincesDeleted)
                    {
                        return $this->responseErrorCode(
                            trans('message.txt_deleted_failure', ['attribute' => trans('message.zone_province')]),
                            config('constants.error_code.zone_province_delete_fail_zoneprovinces'),
                            Response::HTTP_NOT_FOUND
                        );
                    }
                }
            }

        }
        return $this->responseSuccess(trans('message.txt_updated_successfully', ['attribute' => trans('message.zone_province')]));
    }

    /**
     * @OA\Get(
     *      path="/admin/zones/{id}/provinces",
     *      operationId="getProvinceByZoneId",
     *      tags={"Zones"},
     *      summary="Get provinces information by zone id",
     *      description="Returns province data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Zone id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
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
     * Show provinces by zone id
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProvinceById($id)
    {
        $zones = new ZoneRepository();
        $zone = $zones->find($id);
        if (empty($zone)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.zone')]),
                config('constants.error_code.zone_not_found_getzones'),
                Response::HTTP_NOT_FOUND
            );
        }
        $zoneProvince = new ZoneProvinceRepository();
        $province = $zoneProvince->getProvinceByZoneId($id);
        if (empty($province)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.zone')]),
                config('constants.error_code.province_not_found_getprovincesbyzoneid'),
                Response::HTTP_NOT_FOUND
            );
        };
        return ProvinceNameResource::collection($province)->additional(['status' => 'OK'])->response();
    }

     /**
     * @OA\Delete(
     *      path="/admin/zones",
     *      operationId="deleteZone",
     *      tags={"Zones"},
     *      summary="Delete existing zone",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Zone id",
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
     *         response=400,
     *         description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */

    /**
     * Delete zone
     *
     * @param ZoneDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(ZoneDeleteRequest $request)
    {
        try {
            $params = [
                'ids' => $request->ids
            ];
            $zone = new ZoneRepository();
            $zoneDeleted = $zone->destroyArr($params['ids']);
            if (!$zoneDeleted) {
                return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.zone')]));
            }
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.zone')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.zone')]));
        }
    }
}
