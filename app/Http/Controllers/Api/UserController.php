<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\UserChangePasswordPutRequest;
use App\Http\Requests\UserDeleteRequest;
use App\Http\Requests\UserPatchRequest;
use App\Http\Requests\UserPostRequest;
use App\Http\Requests\UserProfilePutRequest;
use App\Http\Requests\UserPutRequest;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Traits\PaginationTrait;
use App\Traits\UserPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use UserPermissionTrait, PaginationTrait;
    protected $userRepository;
    protected $roleRepository;

    public function __construct(
        UserRepository $userRepository,
        RoleRepository $roleRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->roleRepository = $roleRepository;
    }

    /**
     * @OA\Get(
     *      path="/admin/users",
     *      operationId="getUsersList",
     *      tags={"Users"},
     *      summary="Get list of users",
     *      description="Returns list of users",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="search",
     *      in="query",
     *    @OA\Schema(
     *      type="string"
     *          )
     *      ),
     *   @OA\Parameter(
     *      name="per_page",
     *      in="query",
     *   @OA\Schema(
     *       type="integer"
     *          )
     *      ),
     *   @OA\Parameter(
     *      name="current_page",
     *      in="query",
     *   @OA\Schema(
     *      type="integer"
     *         )
     *      ),
     *
     *   @OA\Response(
     *      response=200,
     *      description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *  @OA\Response(
     *      response=403,
     *      description="Forbidden"
     *      )
     *)
     **/
    /**
     *
     * @param PaginationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(PaginationRequest $request)
    {
        $params = [
            'search' => $request->search ?? '',
            'per_page' => $request->per_page ?? config('constants.pagination.per_page'),
            'page' => $request->page ?? config('constants.pagination.current_page')
        ];
        $data = $this->userRepository->getUsersResource($params);
        return UserResource::collection($data)->additional(['status' => 'OK'])->response();
    }

    /**
     * @OA\Get(
     *      path="/admin/users/{id}",
     *      operationId="getUser",
     *      tags={"Users"},
     *      summary="Get of user",
     *      description="Returns user",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="id of User",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *)
     **/
    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.user')]), 404);
        };

        return UserResource::make($user)->response();
    }

    /**
     * @OA\Post(
     * path="/admin/users",
     *   tags={"Users"},
     *   summary="Create user",
     *   description="Returns user",
     *   operationId="CreateUser ",
     *   security = {{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="first_name",
     *      in="query",
     *      description="firts name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="last_name",
     *      in="query",
     *      description="last name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="username",
     *      in="query",
     *      description="user name of user",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="phone",
     *      in="query",
     *      description="phone of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *    @OA\Parameter(
     *      name="email",
     *      in="query",
     *      description="email of user",
     *      required=false,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="role_id",
     *      in="query",
     *      description="role id of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *  @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="status of User",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *)
     **/
    /**
     *
     * @param UserPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserPostRequest $request)
    {
        try {
            $input = $request->only(['role_id','status','username','first_name','last_name','password','phone_number','email']);
            $input['password'] = Hash::make($request->password);
            if ($request->role_id == config('constants.roles.admin.key')) {
                return $this->responseErrorCode(
                    trans('message.txt_dont_permission_create_user'),
                    config('constants.error_code.create_dont_permission_postuser'),
                    Response::HTTP_FORBIDDEN
                );
            }
            $userCreated = $this->userRepository->create($input);
            if (empty($userCreated)) {
                return $this->responseErrorCode(
                    trans('message.txt_created_failure', ['attribute' => trans('message.user')]),
                    config('constants.error_code.save_user_failed_postuser'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            return UserResource::make($userCreated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.user')]),
                config('constants.error_code.save_user_failed_postuser'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Put(
     *      path="/admin/users/{id}",
     *      operationId="UpdateUser",
     *      tags={"Users"},
     *      summary="Update of user",
     *      description="Returns user updated",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="id of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="first_name",
     *      in="query",
     *      description="first name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="last_name",
     *      in="query",
     *      description="last name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="phone",
     *      in="query",
     *      description="phone of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="email",
     *      in="query",
     *      description="email of user",
     *      required=false,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="role_id",
     *      in="query",
     *      description="role id of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="status of User",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *    @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *)
     **/
    /**
     *
     * @param UserPutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UserPutRequest $request, $id)
    {
        try {
            $role = $this->roleRepository->find($request->role_id);
            if (empty($role)) {
                return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.role')]),
                    Response::HTTP_NOT_FOUND
                );
            }
            $user = $this->userRepository->find($id);
            if (empty($user)) {
                return $this->responseError(trans('message.txt_not_found', ['attribute' => trans('message.user')]),
                    Response::HTTP_NOT_FOUND
                );
            }
            if ($request->password == null || $request->password_confirmation == null) {
                $userUpdated = $this->userRepository->update($id, $request->only(['status','role_id','first_name','last_name','phone_number','email']));
            } else {
                $userData = $request->only(['status','role_id','first_name','last_name','password','phone_number','email']);
                $userData['password'] = Hash::make($request->password);
                $userUpdated = $this->userRepository->update($id, $userData);
            }
            if (!$userUpdated) {
                return $this->responseError(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.user')]),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            return UserResource::make($userUpdated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(
                trans('message.txt_updated_failure', ['attribute' => trans('message.user')]),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Delete(
     *      path="/admin/users/",
     *      operationId="DeleteUser",
     *      tags={"Users"},
     *      summary="Delete of user",
     *      description="Delete one or more Users",
     *      security={{"Bearer":{}}},
     *
     *    @OA\Parameter(
     *          name="ids[]",
     *          description="Campaign id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="array",
     *              @OA\Items(type="integer")
     *          )
     *      ),
     *   @OA\Response(
     *      response=200,
     *       description="Success",
     *      @OA\MediaType(
     *           mediaType="application/json",
     *      )
     *   ),
     *   @OA\Response(
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *
     *)
     **/
    /**
     *
     * @param UserDeleteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(UserDeleteRequest $request)
    {
        try {
            foreach ($request->ids as $id) {
                $user = $this->userRepository->find($id);
                if (empty($user)) {
                    return $this->responseError(
                        trans('message.txt_not_found', ['attribute' => trans('message.user')]),
                        Response::HTTP_NOT_FOUND
                    );
                }
                if (Auth::user()->id == $user->id) {
                    return $this->responseError(
                        trans('message.txt_cant_deleted', ['attribute' => trans('message.user')]),
                        Response::HTTP_FORBIDDEN
                    );
                }
            }
            $userDeleted = $this->userRepository->destroyArr($request->ids);
            if (!$userDeleted) {
                return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.user')]));
            }
            return $this->responseSuccess(trans('message.txt_deleted_successfully', ['attribute' => trans('message.user')]));
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_deleted_failure', ['attribute' => trans('message.user')]));
        }
    }

    /**
     * @OA\Put(
     *      path="/admin/users/status",
     *      operationId="ChangeStatusUser",
     *      tags={"Users"},
     *      summary="change status of user",
     *      description="change status one or more Users",
     *      security={{"Bearer":{}}},
     *
     *  @OA\Parameter(
     *      name="ids[]",
     *      description="user id",
     *      required=true,
     *      in="query",
     *      @OA\Schema(
     *          type="array",
     *          @OA\Items(type="integer")
     *          )
     *      ),
     *   @OA\Parameter(
     *      name="status",
     *      in="query",
     *      description="status of User",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *   @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *
     *)
     **/
    /**
     *
     * @param UserPatchRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(UserPatchRequest $request)
    {
        try {
            $params = [
                'ids' => $request->ids,
                'status' => $request->status,
            ];
            $checkUsers = $this->userRepository->checkUsers($params['ids']);
            if (!$checkUsers) {
                return $this->responseError('User not found!');
            }
            $checkStatus = $this->userRepository->checkStatus($params['status']);
            if (!$checkStatus) {
                return $this->responseError('Status invalid!');
            }
            $userUpdated = $this->userRepository->updateStatusUsers($params);
            if (!$userUpdated) {
                return $this->responseError(
                    trans('message.txt_updated_status_failure', ['attribute' => trans('message.user')]),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $responseData = [
                'data' => $userUpdated,
                'message' => trans('message.txt_updated_status_successfully', ['attribute' => trans('message.user')])
            ];
            return $this->responseSuccess($responseData);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseError(trans('message.txt_updated_status_failure', ['attribute' => trans('message.user')]),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Put(
     *      path="/admin/users/password",
     *      operationId="UpdatePasswordUser",
     *      tags={"Users"},
     *      summary="update password of user",
     *      description="Returns user updated password",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="current_password",
     *      in="query",
     *      description="current_password of user",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="password",
     *      in="query",
     *      description="new password of user ",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="password_confirmation",
     *      in="query",
     *      description="password confirmation of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *    @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *)
     **/
    /**
     *
     * @param UserChangePasswordPutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(UserChangePasswordPutRequest $request, int $id)
    {
        try {
            $user = Auth::user();
            if ($user->id != $id) {
                return $this->responseError(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.user')])
                );
            }
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->responseError(
                    trans('message.txt_check_password_failure')
                );
            }
            $user->password = Hash::make($request->password);
            $user->save();
            if (!$user) {
                return $this->responseError(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.user')]), 
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            return UserResource::make($user)->response();
        } catch (\Throwable $th) {
            return $this->responseError(
                trans('message.txt_updated_failure', ['attribute' => trans('message.user')]), 
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Get(
     *      path="/admin/users/{id}/profile",
     *      operationId="getProfileUser",
     *      tags={"Users"},
     *      summary="Get profile of user",
     *      description="Returns user profile",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="id of User",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *)
     **/
    /**
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfileById($id)
    {
        $user = $this->userRepository->find($id);
        if (empty($user)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found', ['attribute' => trans('message.user')]),
                config('constants.error_code.user_not_found_getuserprofile'),
                Response::HTTP_NOT_FOUND
            );
        };

        return new UserProfileResource($user);
    }

    /**
     * @OA\Put(
     *      path="/admin/users/{id}/profile",
     *      operationId="UpdateProfileUser",
     *      tags={"Users"},
     *      summary="Update of user",
     *      description="Returns user updated",
     *      security={{"Bearer":{}}},
     *
     *   @OA\Parameter(
     *      name="id",
     *      in="path",
     *      description="id of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="username",
     *      in="query",
     *      description="name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="firstname",
     *      in="query",
     *      description="firts name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="lastname",
     *      in="query",
     *      description="last name of User",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="email",
     *      in="query",
     *      description="email of user",
     *      required=true,
     *      @OA\Schema(
     *           type="string"
     *      )
     *   ),
     *   @OA\Parameter(
     *      name="phone",
     *      in="query",
     *      description="phone number of user",
     *      required=true,
     *      @OA\Schema(
     *           type="integer"
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
     *      response=400,
     *      description="Bad Request"
     *   ),
     *    @OA\Response(
     *      response=404,
     *      description="Resource Not Found"
     *   ),
     *)
     **/
    /**
     *
     * @param UserProfilePutRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UserProfilePutRequest $request, $id)
    {
        try {
            $input = $request->only(['first_name', 'last_name', 'phone_number', 'email']);
            $user = $this->userRepository->find($id);
            if (empty($user)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.user')]),
                    config('constants.error_code.user_not_found_putuserprofile'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $userProfileUpdated = $this->userRepository->update($id, $input);
            if (!($userProfileUpdated)) {
                return $this->responseErrorCode(
                    trans('message.txt_updated_failure', ['attribute' => trans('message.user')]),
                    config('constants.error_code.update_user_failed_putuserprofile'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            return new UserProfileResource($userProfileUpdated);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_updated_failure', ['attribute' => trans('message.user')]),
                config('constants.error_code.update_user_failed_putuserprofile'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }
}
