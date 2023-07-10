<?php

namespace App\Http\Controllers\Api\Reward;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerPostRequest;
use App\Http\Requests\EndUserPhoneRequest;
use App\Http\Resources\CustomerPhoneResource;
use App\Http\Resources\CustomerResource;
use App\Models\Code;
use App\Models\Customer;
use App\Repositories\CampaignRepository;
use App\Repositories\CodeRepository;
use App\Repositories\ConfigRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OtpTrackingRepository;
use App\Repositories\ProvinceRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    protected $customerRepository;
    protected $codeRepository;
    protected $campaignRepository;
    protected $userRepository;
    protected $provinceRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CodeRepository $codeRepository,
        CampaignRepository $campaignRepository,
        UserRepository $userRepository,
        ProvinceRepository $provinceRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->codeRepository = $codeRepository;
        $this->campaignRepository = $campaignRepository;
        $this->userRepository = $userRepository;
        $this->provinceRepository = $provinceRepository;
    }

    /**
     * @OA\Post(
     *      path="/enduser/phone/create",
     *      operationId="storeCustomer",
     *      tags={"EndUsers"},
     *      summary="Store new customer",
     *      description="Returns customer data",
     *      @OA\Parameter(
     *          name="phone_number",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="first_name",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="last_name",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="gender",
     *          in="query",
     *          required=false,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="address",
     *          in="query",
     *          required=false,
     *      @OA\Schema(
     *           type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="province_id",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id_card_number",
     *          in="query",
     *          required=false,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */

    /**
     * Create customer
     *
     * @param CustomerPostRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CustomerPostRequest $request)
    {
        try {
            $params = [
                'phone_number' => $request->phone_number,
                'province_id' => $request->province_id,
                'codes' => $request->codes,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'id_card_number' => $request->id_card_number,
                'image' => $request->image
            ];
            $ip = $request->ip();
            $successArr = [];
            $errorArr = [];
            $province = $this->provinceRepository->find($params['province_id']);
            if (empty($province)) {
                return $this->responseErrorCode(
                    trans('message.txt_not_found', ['attribute' => trans('message.province')]),
                    config('constants.error_code.province_not_found_phonecreate'),
                    Response::HTTP_NOT_FOUND
                );
            }
            $extensionValid = ['jpeg','jpg','png','svg','pdf'];
            if (!empty($params['image'])) {
                if (!in_array($params['image']->extension(), $extensionValid)) {
                    return $this->responseErrorCode(
                        'Trường ảnh phải là một tập tin có định dạng: jpeg, jpg, png, svg, pdf.',
                        config('constants.error_code.image_size_large'),
                        Response::HTTP_BAD_REQUEST
                    );
                }
                if ($params['image']->getSize() > 20971520) {
                    return $this->responseErrorCode(
                        'Dung lượng tập tin trong trường Ảnh không được lớn hơn 20480 kB.',
                        config('constants.error_code.image_size_large'),
                        Response::HTTP_BAD_REQUEST
                    );
                }
            }
            if (strlen((int)$params['id_card_number']) < 8) {
                return $this->responseErrorCode(
                    trans('message.txt_invalid', ['attribute' => trans('message.id_card_number')]),
                    config('constants.error_code.province_not_found_phonecreate'),
                    Response::HTTP_BAD_REQUEST
                );
            }
            $input = $request->only(['phone_number', 'province_id', 'codes', 'first_name', 'last_name', 'id_card_number', 'image']);
            $user = $this->userRepository->findByField('role_id', config('constants.roles.admin.key'))->first();
            $input['created_by'] = $user->id;
            $input['status'] = Customer::STATUS_PENDING;
            $campaign = $this->campaignRepository->findByField('code', $request->header('campaign'))->first();
            $arCode = [];
            foreach ($params['codes'] as $code) {
                $codeDetails = $this->codeRepository->getCodeByCodeAndCampaign($code, $campaign->id);
                if (empty($codeDetails)) {
                    $errorArr[] = [
                        'code' => $code,
                        'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                    ];
                } else {
                    if ($codeDetails->status == Code::STATUS_NEW) {
                        $successArr[] = [
                            'code' => $codeDetails->code,
                            'value' => $codeDetails->value
                        ];
                        $arCode[] = $codeDetails;
                    } else {
                        $errorArr[] = [
                            'code' => $codeDetails->code,
                            'message' => trans('message.txt_invalid', ['attribute' => trans('message.code')])
                        ];
                    }
                }
            }
            if (count($errorArr) == count($params['codes'])) {
                return $this->responseErrorCode(
                    trans('message.txt_invalid', ['attribute' => trans('message.code')]),
                    config('constants.error_code.code_invalid_phonecreate')
                );
            }
            $customerCreated = $this->customerRepository->createNewCustomer($input);
            if (!$customerCreated) {
                return $this->responseErrorCode(
                    trans('message.txt_created_failure', ['attribute' => trans('message.customer')]),
                    config('constants.error_code.save_customer_failed_phonecreate'),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }
            $transactionRepo = new TransactionRepository();
            $transactionCreated = $transactionRepo->createTransaction($ip, $customerCreated->id, 'internal');
            foreach ($arCode as $item) {
                $transactionRepo->createTranItem($transactionCreated->id, $item->id, $item->value);
            }
            return CustomerResource::make($customerCreated)->response();
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->responseErrorCode(
                trans('message.txt_created_failure', ['attribute' => trans('message.customer')]),
                config('constants.error_code.save_customer_failed_phonecreate'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }

    /**
     * @OA\Post(
     *      path="/enduser/phone/check",
     *      operationId="checkPhone",
     *      tags={"EndUsers"},
     *      summary="Check phone is valid",
     *      description="Returns checked phone EndUser",
     *      @OA\Parameter(
     *          name="phone_number",
     *          in="query",
     *          required=true,
     *      @OA\Schema(
     *           type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation"
     *       ),
     *       @OA\Response(
     *         response=404,
     *         description="Resource Not Found"
     *   ),
     * )
     */
    /**
     * Check Phone number EndUser
     *
     * @param EndUserPhoneRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPhoneNumber(EndUserPhoneRequest $request)
    {
        $params = [
            'phone_number' => $request->phone_number,
        ];
        $ip = $request->ip();
        $phoneNumber = $this->customerRepository->findByField('phone_number', $params['phone_number'])->first();
        if (empty($phoneNumber)) {
            return $this->responseErrorCode(
                trans('message.txt_not_found_please_create_new_customer', ['attribute' => trans('message.phone_number')]),
                config('constants.error_code.phone_not_found_phonecheck'),
                Response::HTTP_NOT_FOUND
            );
        }
        if ($phoneNumber->status === Customer::STATUS_PENDING) {
            return $this->responseErrorCode(
                trans('message.txt_customer_pending'),
                config('constants.error_code.customer_pending_phonecheck'),
                Response::HTTP_ACCEPTED
            );
        }
        $otpTrackingRepo = new OtpTrackingRepository;
        $configRepository = new ConfigRepository;
        $unblockAfter = $configRepository->getConfigByEntity('otp', 'unblock_after');
        $otpTracking = $otpTrackingRepo->getOtpTrackingByCustomerId($phoneNumber->id, $ip);
        $timesLimit = $configRepository->getConfigByEntity('otp', 'limit');
        $timesLimitVal = $timesLimit ? $timesLimit->value : 3;
        if (!empty($otpTracking) && $otpTracking->previous_time && $otpTracking->times >= $timesLimitVal) {
            $blockedAt = Carbon::parse($otpTracking->previous_time);
            $unblockAfterVal = $unblockAfter ? $unblockAfter->value : 24;
            $unblockAt = $blockedAt->addHours($unblockAfterVal);
            if (Carbon::now() < $unblockAt)
            {
                return $this->responseErrorCode(
                    trans('message.txt_phone_number_blocking'),
                    config('constants.error_code.phone_blocking_phonecheck')
                );
            }
        }
        return new CustomerPhoneResource($phoneNumber);
    }
}
