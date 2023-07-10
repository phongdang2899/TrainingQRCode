<?php

namespace App\Http\Resources;

use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Repositories\CustomerRepository;

class CodeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $campaign = (new CampaignRepository())->find($this->campaign_id);
        $customerItem = (new CustomerRepository())->find($this->customer_id);
        return [
            'code_id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'value' => $this->value,
            'status' => [
                'id' => $this->status,
                'name' => (new CodeRepository())->getStatusCode($this->status)
            ],
            'campaign_id' => $this->campaign_id,
            'campaign_name' => $campaign->name,
            'customer' => CustomerGetResource::make($customerItem),
            'activated_date' => $this->activated_date,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function with($request)
    {
        return [
            'status' => 'OK',
        ];
    }
}
