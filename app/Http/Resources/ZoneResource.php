<?php

namespace App\Http\Resources;

use App\Models\Zone;
use App\Repositories\ZoneRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class ZoneResource extends JsonResource
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
            Zone::STATUS_ACTIVE => 'Hoạt động'
        ];
        // return parent::toArray($request);
        return [
            'zone_id' => $this->id,
            'name' => $this->name,
            'status' => [
                'id' => $this->status,
                'name' => (new ZoneRepository())->getStatusZone($this->status)
            ],
            'created_by' => $this->created_by
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
