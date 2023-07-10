<?php

namespace App\Repositories;

use App\Models\Code;
use App\Models\Customer;
use App\Models\ThirdPartyTransaction;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\TopUpService;
use Illuminate\Support\Facades\DB;

class TopUpRepository extends EloquentRepository
{
    /**
     * Get model name
     * @return string
     */
    public function getModel()
    {
        return ThirdPartyTransaction::class;
    }

    /**
     * Action Top-up
     * @param int $campaignId
     * @param int $userId
     * @param array $config
     * @return bool|int
     */
    public function doTopUp(string $transactionId)
    {
        $transactionRepo = new TransactionRepository();
        $transaction = $transactionRepo->find($transactionId);
        if (!$transaction || (!in_array($transaction->status, [Transaction::STATUS_NEW, Transaction::STATUS_PENDING]))) {
            return false;
        }

        $arTransactionItem = TransactionItem::where([['transaction_id', $transaction->id], ['status', TransactionItem::STATUS_PENDING]])
            ->get();
        if (!$arTransactionItem->count()) return false;

        $customerRepo = new CustomerRepository();
        $customer = $customerRepo->find($transaction->customer_id);
        if (!$customer || $customer->status != Customer::STATUS_ACTIVE) return false;

        $arCodeId = array_column($arTransactionItem->toArray(), 'code_id');
        $codeRepo = new CodeRepository();
        $codePending = $codeRepo->prepareRedeem($arCodeId);
        if (!$codePending) {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_FAIL);
            return false;
        }

        if (!$transactionRepo->updateStatus($transaction->id, Transaction::STATUS_PENDING)) {
            return false;
        }
        $topUp = new TopUpService();
        $arCodeIdSucceed = [];
        $arCodeIdFail = [];

        DB::beginTransaction();
        foreach ($arTransactionItem as $item) {
            $code = $codeRepo->find($item->code_id);
            if (!$this->validQuota($code->campaign_id, $code->value) || !in_array($code->status, [Code::STATUS_NEW, Code::STATUS_PENDING])) {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_FAIL);
                $arCodeIdFail[] = $item->code_id;
                continue;
            }
            if ($topUp->callTopUpApi($transaction->id, $item->code_id, $customer->phone_number, $item->value)) {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_SUCCESS);
                $arCodeIdSucceed[] = $item->code_id;
                $this->updateQuotaCounter($code->campaign_id, $code->value);
            } else {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_FAIL);
                $arCodeIdFail[] = $item->code_id;
            }
        }

        if ($arCodeIdSucceed) {
            $codeSucceed = $codeRepo->updateRedeemSucceed($arCodeIdSucceed, $customer->id);
            if (!$codeSucceed) {
                DB::rollBack();
                return false;
            }
        }
        $hasCompleted = false;
        if ($arCodeIdSucceed && $arCodeIdFail) {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_NOT_COMPLETED);
            $hasCompleted = true;
        } elseif ($arCodeIdSucceed) {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_SUCCESS);
            $hasCompleted = true;
        } else {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_FAIL);
        }
        DB::commit();
        return $hasCompleted;
    }

    /**
     * Action retry Top-up
     * @param string $transactionId
     * @return bool
     */
    public function redoTopUp(string $transactionId)
    {
        $transactionRepo = new TransactionRepository();
        $transaction = $transactionRepo->find($transactionId);
        if (!$transaction || (!in_array($transaction->status, [Transaction::STATUS_NOT_COMPLETED, Transaction::STATUS_FAIL]))) {
            return false;
        }

        $arTransactionItem = TransactionItem::where([['transaction_id', $transaction->id], ['status', TransactionItem::STATUS_FAIL]])
            ->get();
        if (!$arTransactionItem->count()) return false;

        $customerRepo = new CustomerRepository();
        $customer = $customerRepo->find($transaction->customer_id);
        if (!$customer || $customer->status != Customer::STATUS_ACTIVE) return false;

        $codeRepo = new CodeRepository();
        $topUp = new TopUpService();
        $arCodeIdSucceed = [];
        $arCodeIdFail = [];

        DB::beginTransaction();
        foreach ($arTransactionItem as $item) {
            $code = $codeRepo->find($item->code_id);
            if (!$this->validQuota($code->campaign_id, $code->value) || !in_array($code->status, [Code::STATUS_NEW, Code::STATUS_PENDING])) {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_FAIL);
                $arCodeIdFail[] = $item->code_id;
                continue;
            }
            if ($topUp->callTopUpApi($transaction->id, $item->code_id, $customer->phone_number, $item->value)) {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_SUCCESS);
                $arCodeIdSucceed[] = $item->code_id;
                $this->updateQuotaCounter($code->campaign_id, $code->value);
            } else {
                $transactionRepo->updateStatusTransactionItem($transaction->id, $item->code_id, TransactionItem::STATUS_FAIL);
                $arCodeIdFail[] = $item->code_id;
            }
        }

        if ($arCodeIdSucceed) {
            $codeSucceed = $codeRepo->updateRedeemSucceed($arCodeIdSucceed, $customer->id);
            if (!$codeSucceed) {
                DB::rollBack();
                return false;
            }
        }
        $hasCompleted = false;
        if ($arCodeIdSucceed && $arCodeIdFail) {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_NOT_COMPLETED);
            $hasCompleted = true;
        } elseif ($arCodeIdSucceed) {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_SUCCESS);
            $hasCompleted = true;
        } else {
            $transactionRepo->updateStatus($transaction->id, Transaction::STATUS_FAIL);
        }
        DB::commit();
        return $hasCompleted;
    }

    /**
     * Check quota allow redeem
     * @param int $campaignId
     * @param int $campaignType
     * @param int $amount
     * @param bool $needQuota
     * @return bool|int
     */
    public function validQuota(int $campaignId, int $campaignType, int $amount = 1, bool $needQuota = false)
    {
        $configRepo = new ConfigRepository();
        $campaignConfigRepo = new CampaignConfigRepository();
        $quotaCounter = $configRepo->getConfigByEntity('quota', "counter_{$campaignId}_{$campaignType}");
        $currentCounter = 0;
        if (!$quotaCounter) {
            $codeRepo = new CodeRepository();
            $count = $codeRepo->getCountActivated($campaignId, $campaignType);
            $config = $configRepo->add('quota', "counter_{$campaignId}_{$campaignType}", $count);
            $currentCounter = $count;
            if (!$config) {
                return false;
            }
        } else {
            $currentCounter = (int)$quotaCounter->value;
        }
        $quotaConfig = $campaignConfigRepo->getByType($campaignId, $campaignType);
        $configCounter = 0;
        if (!$quotaConfig) {
            return true;
        } else {
            $configCounter = (int)$quotaConfig->quota;
            if ($configCounter == 0) return true;
        }
        if (($currentCounter + $amount) <= $configCounter) {
            return true;
        }
        if ($needQuota) {
            return max($configCounter - $currentCounter, 0);
        }
        return false;
    }

    /**
     * Update counter of quota
     * @param int $campaignId
     * @param int $type
     * @param int $countUpdate
     * @return int
     */
    public function updateQuotaCounter(int $campaignId, int $type, int $countUpdate = 1)
    {
        $configRepo = new ConfigRepository();
        $codeRepo = new CodeRepository();
        $quotaCounter = $configRepo->getConfigByEntity('quota', "counter_{$campaignId}_{$type}");
        if (!$quotaCounter) {
            $count = $codeRepo->getCountActivated($campaignId, $type);
            $configRepo->add('quota', "counter_{$campaignId}_{$type}", $count + $countUpdate);
            return $count + $countUpdate;
        }
        $configRepo->updateValue('quota', (int)$quotaCounter->value + $countUpdate, "counter_{$campaignId}_{$type}");

        return (int)$quotaCounter->value + $countUpdate;
    }
}
