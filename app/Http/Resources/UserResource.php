<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $gender = [
            1 => 'Male',
            0 => 'Female',
        ];
        $status = [
            User::STATUS_ACTIVE => 'Hoạt động',
            User::STATUS_LOCKED => 'Đã khóa'
        ];
        $roleItem = (new RoleRepository())->getById($this->role_id);
        return [
            'user_id' => $this->id,
            'role' => RoleResource::make($roleItem),
            'status' => ($this->status !== null) ? ['id' => $this->status, 'name' => $status[$this->status]] : 'N/A',
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone_number' => $this->phone_number,
            'gender' => ($this->gender !== null) ? $gender[$this->gender] : 'N/A',
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
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
