<?php

namespace App\Repositories;

use App\Models\Province;

class ProvinceRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Province::class;
    }

    public function getProvince()
    {
        return $this->_model->select('*')->get();
    }

    public function getById(int $id)
    {
        return $this->_model->select('*')->where('id', $id)->first();
    }
}
