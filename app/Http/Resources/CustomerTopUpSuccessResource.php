<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerTopUpSuccessResource extends JsonResource
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
            'message'        => $resource['message'],
            'transaction_id' => $resource['transaction_id'],
            'phone_number'   => $resource['phone_number'],
            'total_money'    => $resource['total_money'],
            'success_array'   => $resource['success_array'],
            'error_array'     => $resource['error_array']
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
