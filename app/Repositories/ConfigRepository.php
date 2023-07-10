<?php

namespace App\Repositories;

use App\Models\Config;

class ConfigRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Config::class;
    }

    public function getConfigResource($params)
    {
        $configs = $this->_model->select('entity_id');
        if ($params['search']) {
            $configs = $configs->where('entity_type', 'LIKE', '%' . $params['search'] . '%');
        }
        return $configs->latest()->orderBy('id', 'DESC')->groupBy('entity_id')->get();
    }

    public function getConfigByEntity(string $entityId, string $entityType = '')
    {
        $configs = $this->_model->where('entity_id', $entityId);
        if ($entityType) {
            return $configs->where('entity_type', $entityType)->first();
        }
        return $configs->get();
    }

    public function getQuotaCurrentCounter(int $campaignId, int $type)
    {
        return $this->_model->where('entity_id', 'quota')->where('entity_type', "counter_{$campaignId}_{$type}")->first();
    }

    public function getConfigByEntityId(string $entityId)
    {
        return $this->_model->select('*')->where('entity_id', $entityId)->get();
    }

    public function updateValue(string $entity_id, string $value, string $type)
    {
        return $this->_model->where('entity_id', $entity_id)->where('entity_type', $type)->update(['value' => $value]);
    }

    public function add(string $entityId, string $entityType, string $value, int $status = Config::STATUS_ACTIVE)
    {
        return $this->create([
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'value' => $value,
            'status' => $status,
            'created_by' => (new UserRepository())->getUserIdDefault()
        ]);
    }
}
