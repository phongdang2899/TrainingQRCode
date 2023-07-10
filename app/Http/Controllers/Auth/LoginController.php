<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginPostRequest;
use App\Models\Role;
use App\Models\User;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    protected $userRepo;
    protected $roleRepo;
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        UserRepository $userRepo,
        RoleRepository $roleRepo
    )
    {
        $this->userRepo = $userRepo;
        $this->roleRepo = $roleRepo;
    }

    /**
     * @OA\Post(
     *   path="/admin/users/login",
     *   tags={"Auth"},
     *   summary="Login",
     *   operationId="login",
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
     *      )
     *   ),
     *   @OA\Response(
     *      response=401,
     *       description="Unauthenticated"
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
     *      description="Forbidden"
     *   )
     *)
     **/

    /**
     * Login account
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginPostRequest $request)
    {
        try {
            $credentials = request(['username', 'password']);
            if (!Auth::attempt($credentials)) {
                return self::responseError('Find not found', Response::HTTP_NOT_FOUND);
            }
            $user = User::where('username', $request->username)->first();
            if (!Hash::check($request->password, $user->password, [])) {
                return self::responseError('Find not found', Response::HTTP_NOT_FOUND);
            }
            $arAllowedRole = [
                config('constants.roles.admin.key'),
                config('constants.roles.manager.key'),
                config('constants.roles.member.key')
            ];
            if (!in_array($user->role_id, $arAllowedRole) || $user->status != User::STATUS_ACTIVE) {
                return self::responseError(trans('message.txt_permission_failure'), Response::HTTP_FORBIDDEN);
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
            return self::responseError('Error in Login', Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
