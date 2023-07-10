<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThirdPartyTransaction extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_SENT = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAIL = 3;
    public const STATUS_ERROR = 4;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
        'send_data',
        'received_data'
    ];

    public function createLog(string $name, string $sendJson)
    {
        return $this->fill([
            'name' => $name,
            'status' => self::STATUS_SENT,
            'send_data' => $sendJson,
        ]);
    }

    public function updateLog(string $receivedJson, int $status = self::STATUS_SUCCESS)
    {
        $this->status = $status;
        $this->received_data = $receivedJson;
        return $this->save();
    }

    public function updateLogFail() {
        $this->status = self::STATUS_FAIL;
        return $this->save();
    }

    public function updateLogError() {
        $this->status = self::STATUS_ERROR;
        return $this->save();
    }
}
