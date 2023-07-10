<?php

namespace App\Http\Resources;

use App\Models\Code;
use App\Repositories\ProvinceRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $provinceItem = (new ProvinceRepository())->getById($this->province_id);
        // return parent::toArray($request);
        return [
            'campaign_id' => $this->campaign_id,
            'customer_id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone_number,
            'province' => ProvinceResource::make($provinceItem),
            // 'num_code_activated' => Code::where('customer_id', $this->id)->count(),
            'num_code_activated' => $this->num_code_activated,
            // 'value' => Code::where('customer_id', $this->id)->sum('value'),
            'value' => $this->value
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
