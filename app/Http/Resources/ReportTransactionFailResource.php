<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use App\Repositories\CodeRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\CustomerRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportTransactionFailResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $status = [
            Transaction::STATUS_FAIL => 'Thất bại',
            Transaction::STATUS_NOT_COMPLETED => 'Chưa hoàn thành',
        ];
        $tranRepo = new TransactionRepository();
        $tranItem = $tranRepo->getItemByTransactionId($this->id);
        $totalValue = $tranRepo->getValueTranItem($this->id);

        $CustomerRepo = new CustomerRepository();
        $customerItem = $CustomerRepo->getById($this->customer_id);
        $codeRepo = new CodeRepository();
        $arrCode = $codeRepo->detailsCode($this->id); 
        $time_transaction = $this->updated_at ? $this->updated_at->format('Y-m-d H:i:s') : $this->updated_at;
        return [
            'campaign_id' => $this->campaign_id,
            'transaction_id' => $this->id,
            'code' => $this->code,
            'status' => ($this->status !== null) ? ['id' => $this->status ,'name' => $status[$this->status]] : 'N/A',
            'ip'  => $this->ip,
            'customer' => CustomerGetResource::make($customerItem),
            'created_by' => $this->created_by,
            'item'       => TransactionItemGetResource::make($tranItem),
            'total_value' => $totalValue,
            'time_transaction' => $time_transaction,
            'codes' => $arrCode
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
