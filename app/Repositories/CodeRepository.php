<?php

namespace App\Repositories;

use App\Models\Code;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CodeRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Code::class;
    }

    public function getCodeResource(array $params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $codes = $this->_model->select('codes.*', 'campaigns.name as campaign_name')
            ->join('campaigns', 'codes.campaign_id', '=', 'campaigns.id')
            ->where('codes.campaign_id', $campaignId);
        if (isset($params['status'])) {
            $codes = $codes->where('codes.status', $params['status']);
        }
        if (isset($params['start_date'])) {
            $codes = $codes->whereDate('codes.activated_date', '>=', $params['start_date']);
        }
        if (isset($params['end_date'])) {
            $codes = $codes->whereDate('codes.activated_date', '<=', $params['end_date']);
        }
        if (isset($params['search'])) {
            $codes = $codes->where('codes.code', 'LIKE', '%' . $params['search'] . '%');
        }

        return $codes->latest()->paginate($params['per_page']);
    }

    public function getCodeExport(array $params)
    {
        if (!$params['campaign_id'] || !$params['type']) {
            return false;
        }
        return $this->_model
            ->where('campaign_id', $params['campaign_id'])
            ->where('value', $params['type'])
            ->paginate($params['per_page']);
    }

    //This function to remove duplicate code between successArr and errorArr
    public function getCodeDup($codeValue, &$successArr, &$errorArr)
    {
        foreach ($codeValue as $key => $value) {
            $errorArr[] = [
                'code' => $value,
                'message' => trans('message.txt_out_of_quota')
            ];
            $codeDup = array_search($value, array_column($successArr, 'code'));
            if ($codeDup !== false) {
                array_splice($successArr, $codeDup, 1);
            }
        }
    }

    public function getStatusCode(int $status)
    {
        switch ($status) {
            case Code::STATUS_NEW:
                return 'Mới';
                break;
            case Code::STATUS_ACTIVATED:
                return 'Đã sử dụng';
                break;
            case Code::STATUS_LOCKED:
                return 'Đã khóa';
                break;
            case Code::STATUS_PENDING:
                return 'Đang xử lý';
                break;
            default:
                return 'N/A';
        }
    }

    public function getActivatedCode($params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $codes = $this->_model->select('codes.*', 'campaigns.name as campaign_name', 'campaigns.id as campaign_id')
            ->join('campaigns', 'codes.campaign_id', '=', 'campaigns.id')
            ->join('customers', 'codes.customer_id', '=', 'customers.id')
            ->where('codes.campaign_id', $campaignId);
        if (isset($params['start_date'])) {
            $codes = $codes->whereDate('activated_date', '>=', $params['start_date']);
        }
        if (isset($params['end_date'])) {
            $codes = $codes->whereDate('activated_date', '<=', $params['end_date']);
        }
        if (isset($params['search'])) {
            $codes = $codes->where(function ($query) use ($params) {
                $query->where('codes.code', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('customers.first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('customers.last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(customers.last_name, ' ', customers.first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(customers.first_name, ' ', customers.last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('customers.phone_number', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        $codes = $codes->latest('codes.activated_date')->paginate($params['per_page']);
        return $codes;
    }

    public function checkCodeActive(int $campaignId)
    {
        $code = Code::where('campaign_id', $campaignId)->where('status', Code::STATUS_ACTIVATED)->first();
        if (empty($code))
            return false;

        return true;
    }

    public function getCodeByCodeAndCampaign(string $code, int $campaign_id)
    {
        $codes = $this->_model->select('*')
            ->where('code', $code)
            ->where('campaign_id', $campaign_id)
            ->first();
        return $codes;
    }

    public function updateStatus(string $codes, int $status, int $customer_id, $activated_date)
    {
        $codeUpdated = $this->_model->where('code', $codes)->where('status', Code::STATUS_PENDING);
        $codeUpdated = $codeUpdated->update([
            'status' => $status,
            'customer_id' => $customer_id,
            'activated_date' => $activated_date
        ]);
        return $codeUpdated;
    }

    public function updateStatusTran(string $codes, int $status, int $customer_id, $activated_date)
    {
        $codeUpdated = $this->_model->where('code', $codes)->where('status', Code::STATUS_NEW);
        if (!$codeUpdated) return false;
        $codeUpdated = $codeUpdated->update([
            'status' => $status,
            'customer_id' => $customer_id,
            'activated_date' => $activated_date
        ]);
        return $codeUpdated;
    }

    /**
     * Khóa code để chuẩn bị Top-up
     * @param array $arCodeId
     * @return bool
     */
    public function prepareRedeem(array $arCodeId)
    {
        $arCode = $this->_model->whereIn('id', $arCodeId)->where('status', Code::STATUS_NEW);
        return $arCode->update([
            'status' => Code::STATUS_PENDING,
            'updated_by' => (new UserRepository())->getUserIdDefault()
        ]);
    }

    /**
     * Mở khóa code Redeem thất bại
     * @param array $arCodeId
     * @return bool
     */
    public function rollbackAfterRedeemFail(array $arCodeId)
    {
        $arCode = $this->_model->whereIn('id', $arCodeId)->where('status', Code::STATUS_PENDING);
        return $arCode->update([
            'status' => Code::STATUS_NEW,
            'updated_by' => (new UserRepository())->getUserIdDefault()
        ]);
    }

    /**
     * Cập nhật sau khi Top-up thành công
     * @param array $arCodeId
     * @param int $customerId
     * @return bool
     */
    public function updateRedeemSucceed(array $arCodeId, int $customerId)
    {
        $arCode = $this->_model->whereIn('id', $arCodeId)->where('status', Code::STATUS_PENDING);
        return $arCode->update([
            'status' => Code::STATUS_ACTIVATED,
            'customer_id' => $customerId,
            'activated_date' => Carbon::now()
        ]);
    }

    public function detailsCode($transactionId)
    {
        $errorArr = [];
        $successArr = [];
        $codeRepo = new CodeRepository();
        $transactionRepo = new TransactionRepository();
        $topupRepo = new TopUpRepository();
        $arTransactionItem = $transactionRepo->findByFieldTransItem('transaction_id', $transactionId);
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
        if (count($arTransactionItem)) {
            $codeData = $codeRepo->find($arTransactionItem[0]->code_id);
            $codeGroup = $codeRepo->getCodeValueByGroup(array_column($arTransactionItem->toArray(), 'code_id'));
            foreach ($codeGroup as $key => $codeValue) {
                $redeemAllow = $topupRepo->validQuota($codeData->campaign_id, $key, count($codeValue), true);
                if (!is_bool($redeemAllow) && ($redeemAllow <= 0)) {
                    $this->removeCodeDuplicate($codeValue, $successArr, $errorArr);
                }
                if (!is_bool($redeemAllow) && ($redeemAllow > 0)) {
                    array_splice($codeValue, 0, $redeemAllow);
                    $this->removeCodeDuplicate($codeValue, $successArr, $errorArr);
                }
            }
        }
        $arrCode = array_merge($successArr, $errorArr);
        return $arrCode;
    }

    public function removeCodeDuplicate($codeValue, &$successArr, &$errorArr)
    {
        $codeRepo = new CodeRepository();
        foreach ($codeValue as $value) {
            $code = $codeRepo->findByField('code', $value)->first();
            if (!in_array($value, array_column($errorArr, 'name'))) {
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

    /**
     * Get code value and group
     * @param array $arCodeId
     * @return array
     */
    public function getCodeValueByGroup(array $arCodeId)
    {
        $result = [];
        $arCode = $this->_model->whereIn('id', $arCodeId)->where('status', '!=', Code::STATUS_ACTIVATED)->get();
        if (!$arCode->count()) {
            return $result;
        }
        foreach ($arCode as $item) {
            $result[$item->value][] = $item->code;
        }
        return $result;
    }

    public function deleteAllCode(int $campaignId)
    {
        DB::beginTransaction();
        $codeDelete = Code::where('campaign_id', $campaignId)->delete();
        if ($codeDelete) {
            DB::commit();
            return false;
        }
        DB::rollBack();
        return true;
    }

    public function updateStatusCode(array $codeIds, int $status)
    {
        switch ($status) {
            case Code::STATUS_NEW:
                $codeUpdated = $this->_model->select('id')->whereIn('id', $codeIds)->where('status', Code::STATUS_LOCKED)->update([
                    'status' => Code::STATUS_NEW,
                    'updated_by' => (Auth::id() ?? 1)
                ]);
                break;
            case Code::STATUS_LOCKED:
                $codeUpdated = $this->_model->select('id')->whereIn('id', $codeIds)->where('status', Code::STATUS_NEW)->update([
                    'status' => Code::STATUS_LOCKED,
                    'updated_by' => (Auth::id() ?? 1)
                ]);
                break;
        }

        return $codeUpdated;
    }

    public function getByCode(string $code)
    {
        return $this->_model->select('*')->where('code', $code)->first();
    }

    public function getCountActivated(int $campaignId, int $type)
    {
        return $this->_model->where('campaign_id', $campaignId)->where('value', $type)->where('status', Code::STATUS_ACTIVATED)->get()->count();
    }
}
