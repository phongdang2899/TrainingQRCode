<?php

namespace App\Http\Resources;

use App\Models\Campaign;
use App\Models\CampaignConfig;
use App\Repositories\CodeRepository;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
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
            Campaign::STATUS_DRAFT => 'Nháp',
            Campaign::STATUS_ACTIVE => 'Hoạt động',
            Campaign::STATUS_INACTIVE => 'Ngưng hoạt động'
        ];
        $statusName = [
            'id' => $this->status,
            'name' => $status[$this->status]
        ];
        if ($this->status && ($this->status == Campaign::STATUS_ACTIVE) && ($this->end_date < Carbon::now())) {
            $statusName['name'] = 'Hết hạn';
        }
        $code = (new CodeRepository())->findByField('campaign_id', $this->id)->first();
        $campaignConfig = CampaignConfig::all()->where('campaign_id', $this->id);
        return [
            'campaign_id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'status' => $statusName,
            'reward_url' => $this->reward_url,
            'is_allow_generate' => !$code && count($campaignConfig),
            'is_allow_export' => !!$code,
            'config' => CampaignConfigResource::collection($campaignConfig),
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
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
