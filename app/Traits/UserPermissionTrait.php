<?php
namespace App\Traits;
use Illuminate\Support\Facades\Auth;

trait UserPermissionTrait {

    /**
     * check permission of user
     * @param string $permission
     * @param mixed $user
     * @return boolean
     */
    public function checkUserPermission($permission, $user = null)
    {
        $userCheck = $user ?? Auth::user();
        if (!$userCheck) {
            return false;
        }
        $roleArr = config('constants.roles');
        $permissionArr = [];
        foreach ($roleArr as $key => $value) {
            if ($value['key'] == $userCheck->role_id) {
                $permissionArr = $value['permissions'];
                break;
            }
        }
        if (!count($permissionArr) || !in_array($permission, $permissionArr)) {
            return false;
        }
        return true;
    }
}
?>
