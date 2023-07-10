<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Repositories\CustomerRepository;
use App\Repositories\TopUpRepository;
use Carbon\Carbon;
use App\Http\Requests\CustomerDeleteRequest;
use App\Http\Requests\CustomerPatchRequest;
use App\Http\Requests\CustomerPutRequest;
use App\Http\Requests\CustomerUpdateStatusRequest;
use App\Http\Requests\ImportCustomerPostRequest;
use App\Http\Requests\ListRequest;
use App\Imports\CustomersImport;
use App\Models\Code;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\UserRepository;
use App\Traits\UserPermissionTrait;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    protected $customerRepository;
    protected $codeRepository;
    protected $campaignRepository;
    protected $userRepository;
    protected $transactionRepository;
    protected $configRepository;
    protected $topUpRepository;

    use UserPermissionTrait;

    public function __construct(
        CustomerRepository $customerRepository,
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository,
        UserRepository $userRepository,
        TransactionRepository $transactionRepository,
        ConfigRepository $configRepository,
        TopUpRepository $topUpRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
        $this->configRepository = $configRepository;
        $this->topUpRepository = $topUpRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/customers",
     *      operationId="getCustomersList",
     *      tags={"Customers"},
     *      summary="Get list of customers",
     *      description="Returns list of customers",
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
     * List customers
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'status' => $request->status ?? null,
            'per_page' => $request->per_page ?? Config('constants.pagination.per_page'),
            'page' => $request->page ?? Config('constants.pagination.current_page'),
            'export' => !!$request->export,
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null,
        ];

        $data = $this->customerRepository->getCustomerResource($params);
        return CustomerResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/customers/{id}",
     *      operationId="getCustomerById",
     *      tags={"Customers"},
     *      summary="Get customer information",
     *      description="Returns customer data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Customer id",
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
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $customer = $this->customerRepository->find((int)$id);
        if (empty($customer)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_getcustomers'),
                Response::HTTP_NOT_FOUND
            );
        };

        return CustomerResource::make($customer)->response();
    }

    /**
     * @OA\Put(
     *      path="/admin/customers/{id}",
     *      operationId="updateCustomerById",
     *      tags={"Customers"},
     *      summary="Update customer",
     *      description="Returns customer data",
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
     *          name="phone_number",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="first_name",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="last_name",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="gender",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="address",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="province_id",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id_card_number",
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
     * Update customer
     *
     * @param CustomerPutRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function update(CustomerPutRequest $request, int $id)
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (empty($customer)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.customer_not_found_putcustomers'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $input = $request->only(['phone_number', 'first_name', 'last_name', 'gender', 'address', 'province_id', 'id_card_number', 'brand_name', 'status']);
            $input['updated_by'] = Auth::id();
            $customerUpdated = $this->customerRepository->update($id, $input);
            if (!$customerUpdated) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.update_customer_failed_putcustomers')
                );
            }

            return CustomerResource::make($customerUpdated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.update_customer_failed_putcustomers')
            );
        }
    }*/

    /**
     * @OA\Patch(
     *      path="/admin/customers/{id}/status",
     *      operationId="updateStatusCustomerById",
     *      tags={"Customers"},
     *      summary="Update status customer",
     *      description="Returns customer data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Customer id",
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
     * Change status customer
     *
     * @param CustomerPatchRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function updateStatus(CustomerPatchRequest $request, int $id)
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (empty($customer)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.customer_not_found_patchcustomers'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $input = $request->only(['status']);
            $input['approved_by'] = $input['updated_by'] = Auth::id();
            $input['approved_at'] = Carbon::now();
            $statusUpdated = $this->customerRepository->update($id, $input);
            if (!$statusUpdated) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.update_status_customer_failed_patchcustomers')
                );
            }

            return CustomerResource::make($statusUpdated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.update_status_customer_failed_patchcustomers')
            );
        }
    }*/

    /**
     * @OA\Put(
     *      path="/admin/customers/status",
     *      operationId="updateStatusListCustomer",
     *      tags={"Customers"},
     *      summary="Update status list customer",
     *      description="Returns list customer data",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Customer id",
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
     * Update status list customer
     *
     * @param CustomerUpdateStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function updateStatusList(CustomerUpdateStatusRequest $request)
    {
        try {
            foreach ($request->ids as $id) {
                $params = [
                    'ids'    => $request->ids,
                    'status' => $request->status,
                ];
                $customer = $this->customerRepository->find($id);
                if (empty($customer)) {
                    return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.customer')]), 404);
                }
            }
            $customerUpdated = $this->customerRepository->updateStatus($params);
            if (!$customerUpdated) {
                return $this->responseError(trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]));
            }
            $responseData = [
                'data' => $customerUpdated,
                'message' => trans('message.txt_updated_status_successfully', ['attribute' => trans('message.customer')])
            ];
            return $this->responseSuccess($responseData);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]));
        }
    }*/

    /**
     * Approve new Customer and do Top-up
     *
     * @param CustomerPatchRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approveNewCustomer(CustomerPatchRequest $request, int $id)
    {
        try {
            $successArr = [];
            $errorArr = [];
            $customer = $this->customerRepository->find($id);
            if (empty($customer)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.customer_not_found_patchcustomers'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $input['status'] = $request->status;
            $input['approved_by'] = $input['updated_by'] = Auth::id();
            $input['approved_at'] = Carbon::now();
            $customerUpdated = $this->customerRepository->update($id, $input);
            if (!$customerUpdated) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.update_status_customer_failed_patchcustomers'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $transaction = $this->transactionRepository->findByField('customer_id', $customerUpdated->id)->first();
            if (!$transaction) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.transaction')]),
                    config('constants.error_code.customer_not_found_patchcustomers')
                );
            }
            $arTransactionItem = $this->transactionRepository->findByFieldTransItem('transaction_id', $transaction->id);
            $codeRepo = new CodeRepository();
            foreach ($arTransactionItem as $transactionItem) {
                $code = $codeRepo->find($transactionItem->code_id);
                switch ($code->status) {
                    case Code::STATUS_ACTIVATED:
                        $errorArr[] = [
                            'name' => $code->code,
                            'value' => $code->value ?? 'N/A',
                            'status' => [
                                'id' => config('constants.status_code_after_redeem.activated'),
                                'name' => 'Mã đã sử dụng'
                            ]
                        ];
                        break;
                    case Code::STATUS_PENDING:
                        $errorArr[] = [
                            'name' => $code->code,
                            'value' => $code->value ?? 'N/A',
                            'status' => [
                                'id' => config('constants.status_code_after_redeem.pending'),
                                'name' => 'Mã đang được sử dụng trong giao dịch khác'
                            ]
                        ];
                        break;
                    case Code::STATUS_NEW:
                        $successArr[] = [
                            'name' => $code->code,
                            'value' => $code->value ?? 'N/A',
                            'status' => [
                                'id' => config('constants.status_code_after_redeem.success'),
                                'name' => 'Mã nạp thành công'
                            ]
                        ];
                        break;
                }
            }
            $redeemAllow = null;
            $limitedCount = 0;
            if (count($arTransactionItem)) {
                $codeData = $this->codeRepository->find($arTransactionItem[0]->code_id);
                $codeGroup = $this->codeRepository->getCodeValueByGroup(array_column($arTransactionItem->toArray(), 'code_id'));
                foreach ($codeGroup as $key => $codeValue) {
                    $redeemAllow = $this->topUpRepository->validQuota($codeData->campaign_id, $key, count($codeValue), true);
                    if (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                        $limitedCount += count($codeValue);
                        $this->removeCodeDuplicate($codeValue, $successArr, $errorArr);
                    }
                    if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                        array_splice($codeValue, 0, $redeemAllow);
                        $this->removeCodeDuplicate($codeValue, $successArr, $errorArr);
                    }
                }
            }
            if ($limitedCount == count($arTransactionItem)) {
                // show error limit redeem
                return response()->json([
                    'status' => 'FAIL',
                    'message' => trans('message.txt_top_up_failure'),
                    'codes' => $errorArr
                ], Response::HTTP_BAD_REQUEST);
            }
            if (count($errorArr) == count($arTransactionItem)) {
                return response()->json([
                    'status' => 'FAIL',
                    'message' => trans('message.txt_top_up_failure'),
                    'codes' => $errorArr
                ], Response::HTTP_BAD_REQUEST);
            }
            $callTopup = $this->topUpRepository->doTopUp($transaction->id);
            if (!$callTopup) {
                $this->codeErrorByTopup($arTransactionItem, $errorArr);
                return response()->json([
                    'status' => 'FAIL',
                    'message' => trans('message.txt_top_up_failure'),
                    'codes' => $errorArr
                ], Response::HTTP_BAD_REQUEST);
            }
            $arrCode = array_merge($successArr, $errorArr);
            return CustomerResource::make($customerUpdated)->additional(['codes' => $arrCode])->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_status_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.update_status_customer_failed_patchcustomers'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    public function removeCodeDuplicate($codeValue, &$successArr, &$errorArr) {
        foreach ($codeValue as $value) {
            $code = $this->codeRepository->findByField('code', $value)->first();
            if(!in_array($value, array_column($errorArr, 'name'))) {
                $errorArr[] = [
                    'name' => $code->code,
                    'value' => $code->value ?? 'N/A',
                    'status' => [
                        'id' => config('constants.status_code_after_redeem.out_of_quota'),
                        'name' => 'Mã đã hết hạn ngạch'
                    ]
                ];
            }
            $codeDup = array_search($code->code, array_column($successArr, 'name'));
            if ($codeDup !== false) {
                array_splice($successArr, $codeDup, 1);
            }
        }
    }

    public function codeErrorByTopup($arTransactionItem, &$errorArr)
    {
        foreach ($arTransactionItem as $transactionItem) {
            $code = $this->codeRepository->find($transactionItem->code_id);
            $arrCode[] = $code->code;
        }
        $codeErrorByTopup = array_diff($arrCode, array_column($errorArr, 'name'));
        foreach ($codeErrorByTopup as $value) {
            $code = $this->codeRepository->findByField('code', $value)->first();
            $errorArr[] = [
                'name' => $code->code,
                'value' => $code->value ?? 'N/A',
                'status' => [
                    'id' => config('constants.status_code_after_redeem.topup_fail'),
                    'name' => 'Lỗi do Topup thất bại!'
                ]
            ];
        }
    }

    /**
     * @OA\Delete(
     *      path="/admin/customers/{id}",
     *      operationId="deleteCustomer",
     *      tags={"Customers"},
     *      summary="Delete existing customer",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Customer id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
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
     * Delete customer
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function delete(int $id)
    {
        try {
            $customer = $this->customerRepository->find($id);
            if (empty($customer)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.customer_not_found_deletecustomers'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $customerDeleted = $this->customerRepository->delete($id);
            if (!$customerDeleted) {
                return $this->responseErrorCode(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.delete_customer_failed_deletecustomers')
                );
            }
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.customer')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_deleted_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.delete_customer_failed_deletecustomers')
            );
        }
    }*/

    /**
     * @OA\Delete(
     *      path="/admin/customers",
     *      operationId="deleteListCustomer",
     *      tags={"Customers"},
     *      summary="Delete existing customer",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="ids[]",
     *          description="Customer id",
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
     * Delete list customers
     *
     * @param CustomerDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    /*public function deleteList(CustomerDeleteRequest $request)
    {
        try {
            foreach ($request->ids as $id) {
                $customer = $this->customerRepository->find($id);
                if (empty($customer)) {
                    return $this->responseErrorCode(
                        trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                        config('constants.error_code.customer_not_found_deletecustomerslist'),
                        Response::HTTP_NOT_FOUND
                    );
                }
            }
            $customerDeleted = $this->customerRepository->destroyArr($request->ids);
            if (!$customerDeleted) {
                return $this->responseErrorCode(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.delete_customer_failed_deletecustomerslist')
                );
            }
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.customer')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_deleted_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.delete_customer_failed_deletecustomerslist')
            );
        }
    }*/

    /**
     * @OA\Post(
     *      path="admin/customers/import",
     *      operationId="ImportCustomer",
     *      tags={"Customers"},
     *      summary="customer import",
     *      description="Returns import customer successfully",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="file",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="file"
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
     * Import Customers
     *
     * @param ImportCustomerPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function fileImport(ImportCustomerPostRequest $request)
    {
        $import = new CustomersImport;
        Excel::import($import, $request->file);
        if (!$import->result['successCount']) {
            return $this->responseError(trans('message.txt_import_failure', ['attribute' => trans('message.customer')]));
        }
        return $this->responseSuccess([
            'message' => trans('message.txt_import_successfully', ['attribute' => trans('message.customer')]),
            'errorArr' => $import->result['errorArr'],
            'total_record_success' => $import->result['successCount'],
            'successArr' => $import->result['successArr']
        ]);
    }
}
