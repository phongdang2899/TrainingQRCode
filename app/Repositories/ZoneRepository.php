<?php

namespace App\Repositories;

use App\Models\Zone;
use Illuminate\Support\Facades\DB;

class ZoneRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return Zone::class;
    }

    public function updateName(string $name, int $id)
    {
        $zone = $this->_model->where('id', $id);
        $zone->update([
            'name' => $name
        ]);
        return $zone;
    }

    public function ReportByZone($params)
    {
        $campaignId = ($params['campaign_id'] > 0) ? $params['campaign_id'] : (new CampaignRepository())->getCampaignDefault();
        $zones = $this->_model->select('zones.id', 'zones.name', 'codes.campaign_id',
            DB::raw('COUNT(DISTINCT customers.id) as amount_customer'),
            DB::raw('SUM(codes.value) as total_value'),
            DB::raw('COUNT(codes.id) as amount_code_activated'))
            ->leftJoin('zone_provinces', 'zones.id', '=', 'zone_provinces.zone_id')
            ->leftJoin('provinces', 'zone_provinces.province_id', '=', 'provinces.id')
            ->leftJoin('customers', 'provinces.id', '=', 'customers.province_id')
            ->leftjoin('codes', 'customers.id', '=', 'codes.customer_id')
            ->groupBy('zones.id')
            ->where('codes.campaign_id', $campaignId);
        if (isset($params['start_date'])) {
            $zones = $zones->whereDate('codes.activated_date', '>=', $params['start_date']);
        }
        if (isset($params['end_date'])) {
            $zones = $zones->whereDate('codes.activated_date', '<=', $params['end_date']);
        }
        if ($params['export']) {
            $zones = $zones->get();
        } else {
            $zones = $zones->paginate($params['per_page']);
        }
        return $zones;
    }

    public function getZoneResource()
    {
        return $this->_model->select('*')->orderBy('id', 'DESC')->get();
    }

    public function getStatusZone(int $status)
    {
        switch ($status) {
            case Zone::STATUS_ACTIVE:
                return 'Hoạt động';
                break;
            default:
                return 'N/A';
        }
    }
}
