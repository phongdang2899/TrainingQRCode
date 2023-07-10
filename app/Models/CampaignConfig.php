<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampaignConfig extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 0;
    public const STATUS_INACTIVE = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'campaign_id',
        'type',
        'value',
        'quota',
        'status',
        'created_by',
        'updated_by'
    ];
}
