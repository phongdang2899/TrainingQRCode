<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProvinceResource;
use App\Repositories\ProvinceRepository;

class ProvinceController extends Controller
{
    protected $provinceRepository;

    public function __construct(
        ProvinceRepository $provinceRepository
    ) {
        $this->provinceRepository = $provinceRepository;
    }

    /**
     * @OA\Get(
     *      path="/provinces",
     *      operationId="getProvincesList",
     *      tags={"Provinces"},
     *      summary="Get list of provinces",
     *      description="Returns list of provinces",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */

    /**
     * List provinces
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $data = $this->provinceRepository->getProvince();
        return ProvinceResource::collection($data)->additional(['status' => 'OK'])->response();
    }
}
