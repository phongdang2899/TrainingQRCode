<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Code extends Model
{
    use HasFactory;

    public const STATUS_NEW = 1;
    public const STATUS_ACTIVATED = 2;
    public const STATUS_LOCKED = 3;
    public const STATUS_PENDING = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'value',
        'status',
        'campaign_id',
        'customer_id',
        'activated_date',
        'created_by',
        'updated_by'
    ];
}
