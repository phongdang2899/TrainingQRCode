<?php

namespace App\Repositories;

use App\Models\CampaignConfig;

class CampaignConfigRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return CampaignConfig::class;
    }

    public function getCampaignConfigResource($params, int $campaign_id)
    {
        $campaignConfigs = $this->_model->where('campaign_id', $campaign_id);
        if ($params['search']) {
            $campaignConfigs = $campaignConfigs->where(function ($query) use ($params) {
                $query->where('type', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('value', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        return $campaignConfigs->latest()->orderBy('id', 'DESC')->get();
    }

    public function getType(int $campaign_id)
    {
        return $this->_model->select('type')
            ->where('campaign_id', $campaign_id)
            ->get();
    }

    public function getByType(int $campaign_id, int $type)
    {
        return $this->_model->where('campaign_id', $campaign_id)->where('type', $type)->first();
    }

    public function saveMultiple(array $config)
    {
        return $this->_model::upsert($config, [], ['type', 'value']);
    }

    public function saveMultipleWithQuota(array $config)
    {
        return $this->_model::upsert($config, [], ['type', 'value', 'quota']);
    }

    public function checkTypeByCampaignId(int $campaignId, int $type)
    {
        return $this->_model->select('*')
            ->where('campaign_id', $campaignId)
            ->where('type', $type)
            ->first();
    }
}
