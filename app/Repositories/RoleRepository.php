<?php

namespace App\Repositories;

use App\Models\Role;

class RoleRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Role::class;
    }

    public function getRolesExceptAdmin()
    {
        return $this->_model->where('name', '!=', config('constants.roles.admin.name'))->get();
    }

    public function getById(int $id)
    {
        return $this->_model->select('*')->where('id', $id)->first();
    }
}
