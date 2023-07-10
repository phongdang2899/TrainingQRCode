<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Repositories\CustomerRepository;

class ReportCodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $customerItem = (new CustomerRepository())->find($this->customer_id);

        // return parent::toArray($request);
        return [
            'code_id' => $this->id,
            'code' => $this->code,
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $this->campaign_name,
            'customer' => CustomerGetResource::make($customerItem),
            'activated_date' => $this->activated_date,
            'activated_phone' => $this->activated_phone,
            'status' => $this->status,
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
