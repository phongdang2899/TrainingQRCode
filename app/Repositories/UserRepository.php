<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserRepository extends EloquentRepository
{
    /**
     * get model
     * @return string
     */
    public function getModel()
    {
        return User::class;
    }

    public function getUsersResource(array $params, bool $ignoreAdmin = true)
    {
        $user = $this->_model->select('*');
        if ($params['search']) {
            $user = $user->where(function ($query) use ($params) {
                $query->where('users.first_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhere('users.last_name', 'LIKE', '%' . $params['search'] . '%')
                    ->orWhereRaw("CONCAT(users.last_name, ' ', users.first_name) LIKE '%{$params['search']}%'")
                    ->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE '%{$params['search']}%'")
                    ->orWhere('users.username', 'LIKE', '%' . $params['search'] . '%');
            });
        }
        if ($ignoreAdmin) {
            $user->where('role_id', '!=', config('constants.roles.admin.key'));
        }
        return $user->orderBy('id', 'DESC')->paginate($params['per_page']);
    }

    public function updateStatus($params)
    {
        return $this->_model->whereIn('id', $params['ids'])->update(['status' => $params['status']]);
    }

    public function updateStatusUsers($params)
    {
        switch ($params['status']) {
            case User::STATUS_ACTIVE :
                $userUpdates = $this->_model->select('id')->whereIn('id', $params['ids'])->where('status', User::STATUS_LOCKED)->update([
                    'status' => User::STATUS_ACTIVE
                ]);
                break;
            case User::STATUS_LOCKED :
                $userUpdates = $this->_model->select('id')->whereIn('id', $params['ids'])->where('status', User::STATUS_ACTIVE)->update([
                    'status' => User::STATUS_LOCKED
                ]);
                break;
        }

        return $userUpdates;
    }

    public function checkUsers(array $arrayIds)
    {
        $userId = $this->_model->select('*')->whereIn('id', $arrayIds)->get();
        if (count($userId) != count($arrayIds)) {
            return false;
        }
        return $userId;
    }

    public function checkStatus(int $status)
    {
        $checkStatus = in_array($status, [User::STATUS_ACTIVE, User::STATUS_LOCKED]);
        if (!$checkStatus) {
            return false;
        }
        return $checkStatus;
    }

    public function getUserIdDefault()
    {
        if (Auth::id()) return Auth::id();
        $user = $this->findByField('role_id', config('constants.roles.admin.key'))->first();
        return $user->id ?? 1;
    }
}
