<?php

namespace App\Repositories;

use App\Models\Campaign;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignRepository extends EloquentRepository
{

    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Campaign::class;
    }

    public function getCampaignResource($params)
    {
        $campaigns = $this->_model->select('*');
        if ($params['search']) {
            $campaigns = $campaigns->where('name', 'LIKE', '%' . $params['search'] . '%');
        }
        if (isset($params['start_date'])) {
            $campaigns = $campaigns->whereDate('start_date', '>=', $params['start_date']);
        }
        if (isset($params['end_date'])) {
            $campaigns = $campaigns->whereDate('end_date', '<=', $params['end_date']);
        }
        return $campaigns->latest()->paginate($params['per_page']);
    }

    public function getCampaignDefault()
    {
        $campaigns = $this->_model->select('id', 'name', 'status')
            ->where('start_date', '<', Carbon::now())
            ->where('end_date', '>', Carbon::now())
            ->where('status', Campaign::STATUS_ACTIVE)
            ->latest('updated_at')->first();
        if (empty($campaigns)) {
            $campaignsLatest = $this->_model->select('id', 'name', 'status')
                ->where('start_date', '<', Carbon::now())
                ->where('end_date', '>', Carbon::now())
                ->latest('updated_at')->first();
            if (empty($campaignsLatest)) {
                return false;
            }
            $campaigns = $campaignsLatest;
        }

        return $campaigns->id;
    }

    public function getCampaignCustomerInvited(int $id)
    {
        return $this->_model->select('campaigns.id', 'campaigns.name',
            DB::raw('COUNT(codes.id) as total_code_activated'),
            DB::raw('SUM(codes.value) as total_value_code_activated'))
            ->join('codes', 'campaigns.id', 'codes.campaign_id')
            ->where('codes.customer_id', $id)
            ->groupBy('campaigns.id')
            ->get();
    }

    public function checkCodeExist(int $id)
    {
        return $this->_model->select('*')
            ->join('codes', 'campaigns.id', '=', 'codes.campaign_id')
            ->where('campaigns.id', $id)
            ->first();
    }
}
