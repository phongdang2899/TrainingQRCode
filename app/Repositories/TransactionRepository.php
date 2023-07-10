<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionRepository extends EloquentRepository
{

    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Transaction::class;
    }

    public function updateStatus(string $transaction_id, int $status)
    {
        return $this->_model->where('id', $transaction_id)->update(['status' => $status]);
    }

    public function createTransaction(string $ip, int $customerId, string $source)
    {
        return Transaction::create([
            'code' => 'code',
            'status' => Transaction::STATUS_NEW,
            'source' => $source,
            'ip' => $ip,
            'customer_id' => $customerId,
            'created_by' => config('constants.roles.admin.key')
        ]);
    }

    public function getTranById(string $transaction_id)
    {
        return $this->_model->where('id', $transaction_id)->first();
    }

    public function updateStatusTransactionItem(string $transaction_id, int $code_id, int $status)
    {
        return TransactionItem::where('transaction_id', $transaction_id)->where('code_id', $code_id)->update(['status' => $status]);
    }

    public function createTranItem(string $tranId, int $codeId, int $codeValue)
    {
        return TransactionItem::create([
            'transaction_id' => $tranId,
            'code_id' => $codeId,
            'value' => $codeValue,
            'status' => TransactionItem::STATUS_PENDING
        ]);
    }

    public function findByFieldTransItem($column, $value)
    {
        return TransactionItem::where($column, $value)->get();
    }

    public function getTranFailAndCompleted($params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $transaction = $this->_model->select('transactions.*', 'customers.first_name', 'customers.last_name', 'customers.phone_number as customer_phone', 'codes.campaign_id')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->join('transaction_items', 'transactions.id', 'transaction_items.transaction_id')
            ->join('codes', 'transaction_items.code_id', 'codes.id')
            ->whereIn('transactions.status', [Transaction::STATUS_NOT_COMPLETED, Transaction::STATUS_FAIL])
            ->where('codes.campaign_id', $campaignId);
        if ($params['search']) {
            $transaction = $transaction->where(function ($query) use ($params) {
                $query->where('customers.first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('customers.last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(customers.last_name, ' ', customers.first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(customers.first_name, ' ', customers.last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('customers.phone_number', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        if ($params['export']) {
            $transaction = $transaction->groupBy('transactions.id')->latest('transactions.created_at')->get();
        } else {
            $transaction = $transaction->groupBy('transactions.id')->latest('transactions.created_at')->paginate($params['per_page']);
        }
        return $transaction;
    }

    public function getTransactionToday($params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $transactionItems = $this->subQueryTransactionItem($campaignId);
        $transactionToday = $this->_model->select(
            'transactions.*',
            'customers.first_name',
            'customers.last_name',
            'customers.phone_number as customer_phone',
            'transaction_items.campaign_id'
        )
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->joinSub($transactionItems, 'transaction_items', function ($join) {
                $join->on('transaction_items.transaction_id', '=', 'transactions.id');
            });
        if ($params['start_date'] || $params['end_date']) {
            if (isset($params['start_date'])) {
                $transactionToday = $transactionToday->whereDate('transactions.updated_at', '>=', $params['start_date']);
            }
            if (isset($params['end_date'])) {
                $transactionToday = $transactionToday->whereDate('transactions.updated_at', '<=', $params['end_date']);
            }
        } else {
            $date = $params['date'] ?? Carbon::today();
            $transactionToday = $transactionToday->whereDate('transactions.updated_at', $date);
        }
        if ($params['search']) {
            $transactionToday = $transactionToday->where(function ($query) use ($params) {
                $query->where('customers.first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('customers.last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(customers.last_name, ' ', customers.first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(customers.first_name, ' ', customers.last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('customers.phone_number', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        return $transactionToday->groupBy('transactions.id')->latest('transactions.created_at')->paginate($params['per_page']);
    }

    public function getDataTransactionTodayChart($params)
    {
        $date = ($params['date']) ? $params['date'] : Carbon::today();
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $transactionItems = $this->subQueryTransactionItem($campaignId);
        $transactionToday = $this->_model->select(
            DB::raw('HOUR(transactions.updated_at) as hour'),
            DB::raw('COUNT(HOUR(transactions.updated_at)) as num'),
            'transaction_items.campaign_id'
        )
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->joinSub($transactionItems, 'transaction_items', function ($join) {
                $join->on('transaction_items.transaction_id', '=', 'transactions.id');
            })
            ->whereDate('transactions.updated_at', $date);
        if (isset($params['status'])) {
            $transactionToday = $transactionToday->where('transactions.status', $params['status']);
        }
        return $transactionToday->latest('transactions.created_at')->groupBy(DB::raw('HOUR(transactions.updated_at)'))->get();
    }

    public function getDataTransactionMonthChart($params)
    {
        $month = ($params['month']) ? $params['month'] : Carbon::now()->month;
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $transactionItems = $this->subQueryTransactionItem($campaignId);
        $transactionToday = $this->_model->select(
            DB::raw('DATE(transactions.updated_at) as date'),
            DB::raw('COUNT(DATE(transactions.updated_at)) as num'),
            'transaction_items.campaign_id'
        )
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->joinSub($transactionItems, 'transaction_items', function ($join) {
                $join->on('transaction_items.transaction_id', '=', 'transactions.id');
            })
            ->whereMonth('transactions.updated_at', $month);
        if (isset($params['status'])) {
            $transactionToday = $transactionToday->where('transactions.status', $params['status']);
        }
        return $transactionToday->latest('transactions.created_at')->groupBy(DB::raw('DATE(transactions.updated_at)'))->get();
    }

    private function subQueryTransactionItem($campaignId)
    {
        return TransactionItem::select('transaction_id', 'codes.campaign_id')
            ->join('codes', 'transaction_items.code_id', '=', 'codes.id')
            ->where('codes.campaign_id', $campaignId)
            ->groupBy('transaction_id');
    }
    public function getItemByTransactionId(string $transaction_id)
    {
        return TransactionItem::where('transaction_id', $transaction_id)->first();
    }

    public function getValueTranItem(string $transaction_id)
    {
        return TransactionItem::select('value')
//            ->where('status', TransactionItem::STATUS_SUCCESS)
            ->where('transaction_id', $transaction_id)
            ->sum('value');
    }

    public function getPhoneNumber(string $transaction_id)
    {
        return $this->_model->select('customers.*')
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->where('transactions.id', $transaction_id)->first();
    }
}
