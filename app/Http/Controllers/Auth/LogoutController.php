<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Logout Controller
    |--------------------------------------------------------------------------
    */

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $userCurToken = $user->currentAccessToken();
            if ($userCurToken) {
                $hasDeleted = $user->tokens()->where('id', $userCurToken->id)->delete();
            }
            return self::responseSuccess(['Successfully logout']);
        } catch (\Exception $error) {
            return self::responseError('Error in Logout', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
