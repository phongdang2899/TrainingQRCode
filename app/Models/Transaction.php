<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Uuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes, Uuids;

    public const STATUS_PENDING = 0;
    public const STATUS_NEW = 1;
    public const STATUS_SUCCESS = 2;
    public const STATUS_FAIL = 3;
    public const STATUS_NOT_COMPLETED = 4;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'status',
        'source',
        'ip',
        'phone_number',
        'customer_id',
        'created_by',
        'updated_by'
    ];
}
