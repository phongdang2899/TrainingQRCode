<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransactionResendTopUpPostRequest;
use App\Models\Code;
use App\Models\Customer;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Repositories\CodeRepository;
use App\Repositories\TopUpRepository;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    protected $transactionRepository;

    public function __construct(
        TransactionRepository $transactionRepository
    )
    {
        $this->transactionRepository = $transactionRepository;
    }

    /**
     * @OA\Post(
     *      path="admin/transactions/redeem/retry",
     *      operationId="tryRedeemTranResend",
     *      tags={"Transactions"},
     *      summary="Redeem again by Admin",
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
     * Get redeem again by admin
     *
     * @param TransactionResendTopUpPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function redeemTranFailAndNotCompleted(TransactionResendTopUpPostRequest $request)
    {
        $params = [
            'transaction_id' => $request->transaction_id
        ];
        $transaction = $this->transactionRepository->find($params['transaction_id']);
        if (empty($transaction)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.transaction')]),
                config('constants.error_code.transaction_not_found_redeemretry'),
                Response::HTTP_NOT_FOUND
            );
        }

        $customer = $this->transactionRepository->getPhoneNumber($params['transaction_id']);
        if ($customer && $customer->status != Customer::STATUS_ACTIVE) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.customer')]),
                config('constants.error_code.customer_not_found_redeemretry'),
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

        $errorArr = [];
        $successArr = [];
        $campaignId = 0;
        $codeRepo = new CodeRepository();
        foreach ($transactionItems as $transactionItem) {
            $codeDetail = $codeRepo->find($transactionItem->code_id);
            if (empty($codeDetail)) {
                $errorArr[] = [
                    'code' => $codeDetail->code,
                    'message' => trans('message.txt_not_found', ['attribute' => trans('message.code')])
                ];
            } else {
                $arrCodeId[] = $codeDetail->id;
                if (!$campaignId) $campaignId = $codeDetail->campaign_id;
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
        $topUpRepo = new TopUpRepository();
        $codeGroup = $codeRepo->getCodeValueByGroup($arrCodeId);
        foreach ($codeGroup as $key => $codeValue) {
            $redeemAllow = $topUpRepo->validQuota($campaignId, $key, count($codeValue), true);
            if (!is_bool($redeemAllow) && ($redeemAllow < 1)) {
                $limitedCount += count($codeValue);
            }
            if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                array_splice($codeValue, 0, $redeemAllow);
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            } elseif (!is_bool($redeemAllow) && ($redeemAllow == 0)) {
                $codeRepo->getCodeDup($codeValue, $successArr, $errorArr);
            }
        }

        $totalValue = 0;
        foreach ($successArr as $v) {
            $totalValue += $v['value'];
        }

        if ($limitedCount == count($transactionItems)) {
            // show error limit redeem
            return $this->responseError(
                trans('Code out of number!')
            );
        }

        $callTopUp = $topUpRepo->redoTopUp($params['transaction_id']);
        if (!$callTopUp) {
            return $this->responseErrorCode(trans('message.txt_top_up_failure'),
                config('constants.error_code.failed_top_up_redeemretry')
            );
        }

        return $this->responseSuccess([
            'message' => trans('message.txt_top_up_successfully'),
            'phone_number' => $customer->phone_number,
            'total_money' => $totalValue,
            'success_array' => $successArr,
            'error_array' => $errorArr,
        ]);
    }

    /**
     * @OA\Delete(
     *      path="/admin/transactions/{id}",
     *      operationId="deleteTransaction",
     *      tags={"Transactions"},
     *      summary="Delete existing transaction",
     *      description="Deletes a record and returns no content",
     *      security={{"Bearer":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Transaction id",
     *          required=true,
     *          in="path",
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
     * Delete transaction
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(string $id)
    {
        try {
            $transactionRepo = new TransactionRepository();
            $transaction = $transactionRepo->find($id);
            if (empty($transaction)) {
                return $this->responseError(
                    trans('message.txt_not_found', ['attribute' => trans('message.transaction')])
                );
            }
            if (!in_array($transaction->status, [Transaction::STATUS_NEW, Transaction::STATUS_FAIL])) {
                return $this->responseError(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.transaction')])
                );
            }
            $arTransactionItemSuccess = TransactionItem::where([['transaction_id', $transaction->id], ['status', TransactionItem::STATUS_SUCCESS]])->get();
            if ($arTransactionItemSuccess->count()) {
                return $this->responseError(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.transaction')])
                );
            }
            DB::beginTransaction();
            $arTransactionItem = TransactionItem::where([['transaction_id', $transaction->id], ['status', '!=', TransactionItem::STATUS_SUCCESS]])->get();
            if ($arTransactionItem->count()) {
                $arCodeId = array_column($arTransactionItem->toArray(), 'code_id');
                $codeRepo = new CodeRepository();
                $codeRepo->rollbackAfterRedeemFail($arCodeId);
            }
            $transactionDeleted = $transactionRepo->delete($id);
            if (!$transactionDeleted) {
                DB::rollBack();
                return $this->responseError(
                    trans('message.txt_deleted_failure', ['attribute' => trans('message.transaction')])
                );
            }
            DB::commit();
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.transaction')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.transaction')]));
        }
    }
}
