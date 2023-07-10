<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CodeErrorResource extends JsonResource
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
        return [
            'message'     => $resource['message'],
            'number_curr' => $resource['number_curr'],
            'total_number_mistakes_allowed' => $resource['total_number_mistakes_allowed'],
            'error_code'  => $resource['error_code']
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
            'status' => 'FAIL',
        ];
    }
}
