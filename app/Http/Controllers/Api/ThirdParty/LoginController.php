<?php

namespace App\Http\Controllers\Api\ThirdParty;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPostRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * @OA\Post(
     *   path="/3rd/login",
     *   tags={"ThirdParty"},
     *   summary="loginThirdParty",
     *   operationId="loginThirdParty",
     *
     *   @OA\Parameter(
     *      name="username",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="password",
     *      in="query",
     *      required=true,
     *      @OA\Schema(
     *          type="string"
     *      )
     *   ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *           example={
     *              "status": "OK",
     *               "data": {
     *                   "access_token": "203|oCh0OiN0I93DeTJrFOwJoK5IWaQG6dJFXsc3gvPC",
     *                   "token_type": "Bearer",
     *                   "authUser": {
     *                       "id": 28,
     *                       "username": "system",
     *                       "first_name": "Hoàng Đức",
     *                       "last_name": "Hạnh",
     *                       "gender": 0,
     *                       "phone_number": "0961552976",
     *                       "email": "Hoangducdaihanh.300599@gmail.com",
     *                       "avatar": null,
     *                       "status": 1,
     *                       "role_id": 4,
     *                       "email_verified_at": "2021-07-29T13:04:11.000000Z",
     *                       "created_at": "2021-07-29T13:04:11.000000Z",
     *                       "updated_at": "2021-07-27T21:24:31.000000Z",
     *                       "deleted_at": null,
     *                       "role": {
     *                           "id": 4,
     *                           "name": "Hệ thống"
     *                       }
     *                   }
     *               }
     *           }
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *      description="Unauthenticated",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status": "FAIL",
     *                   "message": "Unauthorized"
     *                     },
     *                 )
     *             )
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="not found"
     *   ),
     *   @OA\Response(
     *      response=403,
     *      description="Forbidden",
     *      @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *              example={
     *                   "status": "FAIL",
     *                   "message": "Bạn không được phép thực hiện yêu cầu này!"
     *                     },
     *                 )
     *             )
     *   ),
     *     @OA\Response(
     *          response=422,
     *          description="Unprocessable Entity",
     *          @OA\MediaType(
     *                 mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "message": "Dữ liệu đưa vào không hợp lệ.",
     *                       "errors": {
     *                       "password": {
     *                               "Trường mật khẩu phải có tối thiểu 8 kí tự!"
     *                               },
     *                     },
     *                  },
     *                 )
     *            )
     *      ),
     *)
     **/

     /**
     * Login system account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginPostRequest $request)
    {
        try {
            $user = User::where('username', $request->username)->first();
            if (empty($user) || !Hash::check($request->password, $user->password, [])) {
                return self::responseError('Unauthorized', Response::HTTP_UNAUTHORIZED);
            }
            if ($user->role_id != config('constants.roles.system.key') || $user->status != User::STATUS_ACTIVE) {
                return self::responseError(trans('message.txt_permission_failure'), Response::HTTP_FORBIDDEN);
            }
            $credentials = request(['username', 'password']);
            if (!Auth::attempt($credentials)) {
                return self::responseError('Unauthorized', Response::HTTP_UNAUTHORIZED);
            }
            $authUser = Auth::user();
            $role = Role::select('id', 'name')->where('id', $authUser->role_id)->first();
            if (!empty($role)) {
                $authUser->role = $role;
            }
            $tokenResult = $user->createToken('authToken')->plainTextToken;
            $dataResponse = [
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'authUser' => Auth::user()
            ];
            return self::responseSuccess($dataResponse);
        } catch (\Exception $error) {
            return self::responseError('Error in Login', 400);
        }
    }
}
