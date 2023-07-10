<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPhoneErrorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->resource;
        // return parent::toArray($request);
        $data = [
            'message' => $resource['message'],
            'error_code' => $resource['error_code']
        ];
        if ($resource['link']) {
            $data['link'] = $resource['link'];
        }
        return $data;
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
            'status' => 'FAIL',
        ];
    }
}
