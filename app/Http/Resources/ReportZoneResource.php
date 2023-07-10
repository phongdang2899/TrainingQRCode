<?php

namespace App\Http\Resources;

use App\Models\Code;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);
        return [
            'campaign_id' => $this->campaign_id,
            'zone_name' => $this->name,
            'amount_customer' => $this->amount_customer,
            'total_value' => $this->total_value,
            'amount_code_activated' => $this->amount_code_activated
        ];
    }
}
