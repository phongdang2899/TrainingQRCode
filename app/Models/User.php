<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    public const STATUS_ACTIVE = 0;
    public const STATUS_LOCKED = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'gender',
        'phone_number',
        'email',
        'avatar',
        'password',
        'status',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function hasPermission(string $permission)
    {
        if ($this->status != static::STATUS_ACTIVE) return false;
        switch ($permission) {
            case 'admin':
                return ($this->role_id == config('constants.roles.admin.key'));
                break;
            case 'manager':
                return (in_array($this->role_id, [
                    config('constants.roles.admin.key'),
                    config('constants.roles.manager.key'),
                ]));
                break;
            case 'member':
                return (in_array($this->role_id, [
                    config('constants.roles.admin.key'),
                    config('constants.roles.manager.key'),
                    config('constants.roles.member.key')
                ]));
                break;
            case 'system':
                return ($this->role_id == config('constants.roles.system.key'));
                break;
        }
        return false;
    }
}
