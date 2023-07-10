<?php

namespace App\Http\Resources;

use App\Models\Customer;
use App\Repositories\CustomerRepository;
use App\Repositories\ProvinceRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
        $gender = [
            1 => 'Male',
            0 => 'Female',
        ];
        $status = [
            Customer::STATUS_PENDING => 'Đợi phê duyệt',
            Customer::STATUS_ACTIVE => 'Hoạt động',
            Customer::STATUS_INACTIVE => 'Bị khóa',
        ];
        $provinceItem = (new ProvinceRepository())->getById($this->province_id);
        $time_created = $this->created_at ? $this->created_at->format('Y-m-d H:i:s') : $this->created_at;
        $imageCustomer = (new CustomerRepository())->getImageByIdCard($this->id_card_number, $this->id);
        return [
          'customer_id' => $this->id,
          'image' => $this->image,
          'same_id_card' => $imageCustomer,
          'phone_number' => $this->phone_number,
          'first_name' => $this->first_name,
          'last_name' => $this->last_name,
          'gender' => ($this->gender !== null) ? $gender[$this->gender] : 'N/A',
          'address' => $this->address,
          'province' => ProvinceResource::make($provinceItem),
          'id_card_number' => $this->id_card_number,
          'brand_name' => $this->brand_name,
          'status' => ($this->status !== null) ? ['id' => $this->status ,'name' => $status[$this->status]] : 'N/A',
          'approved_by' => $this->approved_by,
          'approved_at' => $this->approved_at,
          'created_at' => $time_created,
          'created_by' => $this->created_by,
          'updated_by' => $this->updated_by
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
