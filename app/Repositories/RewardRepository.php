<?php

namespace App\Repositories;

use App\Models\Campaign;
use App\Models\CampaignConfig;
use App\Models\Code;
use App\Services\CodeService;

class RewardRepository extends EloquentRepository
{
    /**
     * Get model name
     * @return string
     */
    public function getModel()
    {
        return Code::class;
    }

    /**
     * Generate GiftCode for Campaign
     * @param int $campaignId
     * @param int $userId
     * @param array $config
     * @return bool|int
     */
    public function createRewardCode(int $campaignId, int $userId, array $config = [])
    {
        $campaign = Campaign::find($campaignId);
        if (!$campaign) {
            return false;
        }

        if (!$config) {
            $arCampConfigs = CampaignConfig::where('campaign_id', $campaign->id)->get();
            if (!$arCampConfigs->count()) {
                return false;
            }
            foreach ($arCampConfigs as $item) {
                if ($item->status == CampaignConfig::STATUS_INACTIVE) continue;
                $config[] = ['type' => (int)$item->type, 'qty' => (int)$item->value];
            }
        }
        if (!$config) {
            return false;
        }

        $model = $this->_model;
        $model->campaign_id = $campaign->id;
        $model->status = Code::STATUS_NEW;
        $model->created_by = $userId;
        $model->created_at = $model->updated_at = now();

        return CodeService::generatesCode($model, $config);
    }
}
