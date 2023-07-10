<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTransactionTodayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $status = [
            Transaction::STATUS_NEW => 'Mới',
            Transaction::STATUS_FAIL => 'Lỗi',
            Transaction::STATUS_NOT_COMPLETED => 'Chưa hoàn thành',
            Transaction::STATUS_SUCCESS => 'Hoàn thành',
            Transaction::STATUS_PENDING => 'Đang xử lý'
        ];
        $time_transaction = $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : $this->updated_at;
        // return parent::toArray($request);
        return [
            'campaign_id' => $this->campaign_id,
            'transaction_id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->customer_phone,
            'status' => ($this->status !== null) ? ['id' => $this->status ,'name' => $status[$this->status]] : 'N/A',
            'total_value' => TransactionItem::where('transaction_id', $this->id)->sum('value'),
            'time_transaction' => $time_transaction
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function with($request)
    {
        return [
            'status' => 'OK',
        ];
    }
}
