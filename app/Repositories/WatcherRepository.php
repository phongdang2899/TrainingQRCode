<?php

namespace App\Repositories;

use App\Models\Watcher;

class WatcherRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return watcher::class;
    }

    public function createByField(string $ip, int $time, string $phone_number = null, int $total_time)
    {
        return $this->_model->create([
            'ip' => $ip,
            'times' => $time,
            'phone_number' => $phone_number,
            'total_times' => $total_time
        ]);
    }

    public function updateByField(int $times, $now, int $total_times, string $phone_number = null, string $ip)
    {
        $watcher = $this->_model->where('phone_number', $phone_number)->where('ip', $ip);
        $watcher->update([
            'times' => $times,
            'previous_time' => $now,
            'total_times' => $total_times,
        ]);
        return $watcher;
    }

    public function updateByTimes(int $time, string $phone_number = null, string $ip)
    {
        $watcher = $this->_model->where('phone_number', $phone_number)->where('ip', $ip);
        $watcher->update([
            'times' => $time
        ]);
        return $watcher;
    }
}
