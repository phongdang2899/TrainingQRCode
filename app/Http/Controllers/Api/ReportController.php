<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\PaginationRequest;
use App\Http\Resources\CustomerDetailResource;
use App\Http\Resources\ReportCodeResource;
use App\Http\Resources\ReportCustomerResource;
use App\Http\Resources\ReportTransactionFailResource;
use App\Http\Resources\ReportTransactionTodayResource;
use App\Http\Resources\ReportZoneResource;
use App\Models\Transaction;
use App\Repositories\CodeRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\ZoneRepository;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    /**
     * @OA\Get(
     *      path="/admin/reports/codes/activated",
     *      operationId="getCodesActivatedList",
     *      tags={"Reports"},
     *      summary="Get list of codes activated",
     *      description="Returns list of codes activated",
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
     * List Codes activated
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getActivatedCode(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page'  => $request->per_page ?? config('constants.pagination.per_page'),
            'page'   => $request->current_page ?? config('constants.pagination.current_page'),
            'campaign_id' => $request->campaign_id ?? null,
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null
        ];

        $codeRepo = new CodeRepository();
        $data = $codeRepo->getActivatedCode($params);
        return ReportCodeResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/reports/customers",
     *      operationId="getCustomerReportList",
     *      tags={"Reports"},
     *      summary="Get list of customer report",
     *      description="Returns list of customer report",
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
     * List customer to report
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerReport(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page' => $request->per_page ?? Config('constants.pagination.per_page'),
            'page' => $request->page ?? Config('constants.pagination.current_page'),
            'campaign_id' => $request->campaign_id ?? null,
            'export' => !!$request->export,
            'sort' => (int)$request->sort ?? null
        ];

        $customerRepo = new CustomerRepository();
        $data = $customerRepo->getCustomerReport($params);
        return ReportCustomerResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    public function showCustomer(int $id)
    {
        $customerRepo = new CustomerRepository();
        $customer = $customerRepo->find((int)$id);
        if (empty($customer)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_getcustomers'),
                Response::HTTP_NOT_FOUND
            );
        };

        return CustomerDetailResource::make($customer)->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/reports/transaction/issue",
     *      operationId="getTransactionsListFailAndNotCompleted",
     *      tags={"Reports"},
     *      summary="Get list fail and not complete of transaction",
     *      description="Returns list fail and not complete of transaction",
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
     * List transaction fail and not complete
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function getTransactionFailAndCompleted(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page'  => $request->per_page ?? config('constants.pagination.per_page'),
            'page'   => $request->current_page ?? config('constants.pagination.current_page'),
            'export' => $request->export ?? null,
            'campaign_id' => $request->campaign_id ?? null,
        ];

        $transactionRepo = new TransactionRepository();
        $transaction = $transactionRepo->getTranFailAndCompleted($params);
        if (empty($transaction)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction')]),
                config('constants.error_code.transactions_not_found_transactions'),
                Response::HTTP_NOT_FOUND
            );
        }
        return ReportTransactionFailResource::collection($transaction)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/reports/transaction/day",
     *      operationId="getTransactionTodayList",
     *      tags={"Reports"},
     *      summary="Get list of transaction today",
     *      description="Returns list of transaction today",
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
     *          name="date",
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
     * List transaction today
     *
     * @param ListRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionToday(ListRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page'  => $request->per_page ?? config('constants.pagination.per_page'),
            'page'   => $request->current_page ?? config('constants.pagination.current_page'),
            'date' => $request->date ?? null,
            'month' =>  $request->month ?? null,
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null,
            'status' => $request->status ?? null,
            'campaign_id' => $request->campaign_id ?? null,
            'get_chart' => !!$request->get_chart,
        ];

        $transactionRepo = new TransactionRepository();
        if ($params['get_chart']) {
            $allTransactionToday = $transactionRepo->getDataTransactionTodayChart($params);
            $allTransactionMonth = $transactionRepo->getDataTransactionMonthChart($params);
            $params['status'] = Transaction::STATUS_FAIL;
            $transactionFailToday = $transactionRepo->getDataTransactionTodayChart($params);
            $transactionFailMonth = $transactionRepo->getDataTransactionMonthChart($params);
            $params['status'] = Transaction::STATUS_SUCCESS;
            $transactionSuccessToday = $transactionRepo->getDataTransactionTodayChart($params);
            $transactionSuccessMonth = $transactionRepo->getDataTransactionMonthChart($params);
            $params['status'] = Transaction::STATUS_NOT_COMPLETED;
            $transactionNotCompletedToday = $transactionRepo->getDataTransactionTodayChart($params);
            $transactionNotCompletedMonth = $transactionRepo->getDataTransactionMonthChart($params);
            $params['status'] = Transaction::STATUS_NEW;
            $transactionNewToday = $transactionRepo->getDataTransactionTodayChart($params);
            $transactionNewMonth = $transactionRepo->getDataTransactionMonthChart($params);
            $data['data_transaction']['date']['all'] = $allTransactionToday;
            $data['data_transaction']['date']['fail'] = $transactionFailToday;
            $data['data_transaction']['date']['not_completed'] = $transactionNotCompletedToday;
            $data['data_transaction']['date']['new'] = $transactionNewToday;
            $data['data_transaction']['date']['success'] = $transactionSuccessToday;
            $data['data_transaction']['month']['all'] = $allTransactionMonth;
            $data['data_transaction']['month']['fail'] = $transactionFailMonth;
            $data['data_transaction']['month']['new'] = $transactionNewMonth;
            $data['data_transaction']['month']['success'] = $transactionSuccessMonth;
            $data['data_transaction']['month']['not_completed'] = $transactionNotCompletedMonth;
            return $data;
        } else {
            $data = $transactionRepo->getTransactionToday($params);
        }
        return ReportTransactionTodayResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/reports/zone",
     *      operationId="getZoneReportList",
     *      tags={"Reports"},
     *      summary="Get list of zone report",
     *      description="Returns list of zone report",
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
     *      @OA\Parameter(
     *          name="export",
     *          in="query",
     *      @OA\Schema(
     *           type="boolean"
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
     * List zone to report
     *
     * @param PaginationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getZoneReport(PaginationRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page' => $request->per_page ?? config('constants.pagination.per_page'),
            'page' => $request->page ?? config('constants.pagination.current_page'),
            'start_date' => $request->start_date ?? null,
            'end_date' => $request->end_date ?? null,
            'export' => $request->export ?? false,
            'campaign_id' => $request->campaign_id ?? null,
        ];

        $zoneRepo = new ZoneRepository();
        $data = $zoneRepo->ReportByZone($params);
        return ReportZoneResource::collection($data)->additional(['status' => 'OK'])->response();
    }
}
