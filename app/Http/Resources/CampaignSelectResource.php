<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignSelectResource extends JsonResource
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
            Campaign::STATUS_ACTIVE => 'Hoạt động',
            Campaign::STATUS_DRAFT => 'Nháp',
            Campaign::STATUS_INACTIVE => 'Ngưng hoạt động'
        ];
        $statusName = [
            'id' => 0,
            'name' => 'N/A'
        ];
        if ($this->status) {
            if ($this->end_date < Carbon::now()) {
                $statusName['name'] = 'Hết hạn';
            } else {
                $statusName['id'] = $this->status;
                $statusName['name'] = $status[$this->status];
            }
        }
        // return parent::toArray($request);
        return [
            'campaign_id' => $this->id,
            'name' => $this->name,
            'status' => $statusName,
            'is_allow_generate' => $this->is_allow_generate,
            'is_activate' => $this->is_activate ?? false,
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
