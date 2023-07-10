<?php

namespace App\Repositories;

use App\Models\ZoneProvince;
use Illuminate\Support\Facades\DB;

class ZoneProvinceRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return ZoneProvince::class;
    }

    public function getProvinceIdByZoneId(int $zoneId)
    {
        return $this->_model->select('zone_provinces.province_id')
            ->join('zones', 'zone_provinces.zone_id', '=', 'zones.id')
            ->where('zone_provinces.zone_id', $zoneId)
            ->get();
    }

    public function getProvinceByZoneId(int $zoneId)
    {
        return $this->_model->select('provinces.*')
            ->join('zones', 'zone_provinces.zone_id', '=', 'zones.id')
            ->join('provinces', 'zone_provinces.province_id', '=', 'provinces.id')
            ->where('zone_provinces.zone_id', $zoneId)
            ->get();
    }

    public function findByZoneAndProvince(int $provinceId, int $zoneId)
    {
        return $this->_model->where('province_id', $provinceId)->where('zone_id', $zoneId)->first();
    }

    public function createZoneProvince(int $zoneId, int $provinceId)
    {
        $zoneProvince = $this->_model;
        $zoneProvince->create([
            'zone_id' => $zoneId,
            'province_id' => $provinceId
        ]);
    }

    public function deleteZoneProvince(int $provinceId, int $zoneId)
    {
        $whereArray = array('province_id' => $provinceId, 'zone_id' => $zoneId);
        $query = DB::table('zone_provinces');
        foreach ($whereArray as $field => $value) {
            $query->where($field, $value);
        }
        return $query->delete();
    }
}
