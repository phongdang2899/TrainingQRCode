<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * @OA\SecurityScheme(
     *      securityScheme="Bearer",
     *      in="header",
     *      name="bearerAuth",
     *      type="http",
     *      scheme="bearer",
     * ),
     * @OA\Info(
     *      version="1.0.0",
     *      title="Integration Swagger in Laravel with Passport Auth Documentation",
     *      description="Implementation of Swagger with in Laravel",
     *      @OA\Contact(
     *          email="admin@admin.com"
     *      ),
     *      @OA\License(
     *          name="Apache 2.0",
     *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
     *      )
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     *      description="Demo API Server"
     * )

     *
     *
     */

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public static function responseSuccess($data)
    {
        return response()->json([
            'status' => 'OK',
            'data' => $data
        ]);
    }

    public static function responseError($message, $status = 400)
    {
        return response()->json([
            'status' => 'FAIL',
            'message' => $message
        ], $status);
    }

    public static function responseErrorCode($message, $errorCode, $status = 400)
    {
        return response()->json([
            'status' => 'FAIL',
            'error_code' => $errorCode,
            'message' => $message
        ], $status);
    }

    public static function responseUpdateError($message, $status = 422)
    {
        return response()->json([
            'status' => 'FAIL',
            'message' => $message
        ], $status);
    }
}
