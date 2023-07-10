<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CodePutRequest;
use App\Http\Requests\CodeDeleteRequest;
use App\Http\Requests\ExportCodeRequest;
use App\Http\Requests\ListRequest;
use App\Http\Resources\CampaignResource;
use App\Http\Resources\CodeExportResource;
use App\Http\Resources\CodeResource;
use App\Repositories\CampaignRepository;
use App\Traits\PaginationTrait;
use App\Repositories\CodeRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class CodeController extends Controller
{
    use PaginationTrait;

    protected $codeRepository;
    protected $campaignRepository;

    public function __construct(
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository
    )
    {
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/codes",
     *      operationId="getCodesList",
     *      tags={"Codes"},
     *      summary="Get list of codes",
     *      description="Returns list of codes",
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
     *      @OA\Parameter(
     *          name="campaign_id",
     *          in="query",
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          in="query",
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="export",
     *          in="query",
     *      @OA\Schema(
     *           type="boolean"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="start_date",
     *          in="query",
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="end_date",
     *          in="query",
     *      @OA\Schema(
     *           type="string"
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
     * List Codes
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page' => $request->per_page ?? config('constants.pagination.per_page'),
            'page' => $request->current_page ?? config('constants.pagination.current_page'),
            'campaign_id' => $request->campaign_id ?? null,
            'export' => $request->export ?? false,
            'status' => $request->status ?? null,
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null
        ];

        $data = $this->codeRepository->getCodeResource($params);
        return CodeResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/codes/{id}",
     *      operationId="getCodeById",
     *      tags={"Codes"},
     *      summary="Get code information",
     *      description="Returns code data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Code id",
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
     * Show code by id
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(int $id)
    {
        $code = $this->codeRepository->find($id);
        if (empty($code)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.code')]),
                config('constants.error_code.code_not_found_patchcodes'),
                Response::HTTP_NOT_FOUND
            );
        };

        return new CodeResource($code);
    }

    /**
     * @OA\Put(
     *      path="/admin/codes/status",
     *      operationId="updateStatusCode",
     *      tags={"Codes"},
     *      summary="Update status existing code",
     *      description="Update status records and returns content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Code id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer")
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
     * Cancel code
     *
     * @param CodePutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(CodePutRequest $request)
    {
        try {
            foreach ($request->ids as $id) {
                $code = $this->codeRepository->find($id);
                if (empty($code)) {
                    return $this->responseErrorCode(
                        trans('message.txt_not_found', ['attribute' => trans('message.code')]),
                        config('constants.error_code.code_not_found_putcodes'),
                        Response::HTTP_NOT_FOUND
                    );
                }
            }
            $codeUpdated = $this->codeRepository->updateStatusCode($request->ids, $request->status);

            if (!$codeUpdated) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_status_failure', ['attribute' => trans('message.code')]),
                    config('constants.error_code.update_status_code_failed_putcodes'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            return $this->responseSuccess(trans('message.txt_updated_status_successfully', ['attribute' => trans('message.code')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_status_failure', ['attribute' => trans('message.code')]),
                config('constants.error_code.update_status_code_failed_putcodes'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Delete(
     *      path="/admin/codes",
     *      operationId="deleteCode",
     *      tags={"Codes"},
     *      summary="Delete existing code",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Code id",
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
     * Delete code
     *
     * @param CodeDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(CodeDeleteRequest $request)
    {
        try {
            $params = [
                'campaign_id' => $request->campaign_id
            ];
            $codeActivated = $this->codeRepository->checkCodeActive($params['campaign_id']);
            if ($codeActivated) {
                return $this->responseErrorCode(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.code')]),
                    config('constants.error_code.delete_code_failed_deletecode'),
                    );
            }

            $codeDeleted = $this->codeRepository->deleteAllCode($params['campaign_id']);
            if ($codeDeleted) {
                return $this->responseErrorCode(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.code')]),
                    config('constants.error_code.delete_code_failed_deletecode')
                );
            }

            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.code')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_deleted_failure', ['attribute' => trans('message.code')]),
                config('constants.error_code.delete_code_failed_deletecode'),
                );
        }
    }

    public function exportCode(ExportCodeRequest $request)
    {
        $params = [
            'campaign_id' => (int)$request->campaign_id,
            'type' => $request->type,
            'per_page' => $request->per_page ?? config('constants.pagination.per_page'),
            'page' => $request->current_page ?? config('constants.pagination.current_page')
        ];

        $campaign = $this->campaignRepository->find($params['campaign_id']);
        if (!$campaign) {
            return $this->responseErrorCode(
                trans('message.txt_invalid_campaign'),
                config('constants.error_code.campaign_not_found_getcampaigns'),
                Response::HTTP_NOT_FOUND
            );
        }
        $arCode = $this->codeRepository->getCodeExport($params);
        return CodeExportResource::collection($arCode)
            ->additional(['status' => 'OK', 'campaign' => CampaignResource::make($campaign)])
            ->response();
    }
}
