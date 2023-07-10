<?php

namespace App\Http\Resources;

use App\Models\TransactionItem;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemGetResource extends JsonResource
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
            TransactionItem::STATUS_FAIL => 'Thất bại',
            TransactionItem::STATUS_PENDING => 'Đợi',
            TransactionItem::STATUS_SUCCESS => 'Thành công'
        ];
        // return parent::toArray($request);
        return [
            'transaction_id' => $this->transaction_id,
            'code_id' => $this->code_id,
            'price' => $this->value,
            'status' => ($this->status !== null) ? ['id' => $this->status ,'name' => $status[$this->status]] : 'N/A',
            'transaction_info' => $this->transaction_info
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
        return ['status' => 'OK'];
    }
}
