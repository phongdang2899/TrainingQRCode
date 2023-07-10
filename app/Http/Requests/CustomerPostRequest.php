<?php

namespace App\Http\Requests;

use App\Traits\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerPostRequest extends FormRequest
{
    use Language;

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status'  => 'FAIL',
            'message' => trans('message.txt_data_given_invalid'),
            'errors' => $validator->errors(),
            'error_code' => config('constants.error_code.invalid_data_phonecreate')
        ], Response::HTTP_BAD_REQUEST);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // $limitSize=config('constants.IMAGE_TYPE.LIMIT_SIZE');
        $rule = [
            'phone_number' => 'required|digits:10|unique:customers|regex:' . $this->phoneNumberVN(),
            'first_name' => 'required|regex:' . $this->characterVN(),
            'last_name' => 'required|regex:' . $this->characterVN(),
            'province_id' => 'required|integer',
            'codes'   => 'required|array',
            'codes.*' => 'required|string|regex:/^[A-Za-z0-9._-]+$/',
            'id_card_number' => 'required|string'
        ];
        // if (!empty($this->file('image'))) {
        //     $rule['image'] = 'mimes:jpeg,jpg,png,svg,pdf';
        // }
        if (!empty($this->get('gender'))) {
            $rule['gender'] = 'integer';
        }
        if (!empty($this->get('address'))) {
            $rule['address'] = 'string';
        }
        if (!empty($this->get('brand_name'))) {
            $rule['brand_name'] = 'string';
        }
        if (!empty($this->get('status'))) {
            $rule['status'] = 'integer';
        }
        return $rule;
    }

    public function messages()
    {
        return [
            'phone_number|required' => trans('message.txt_invalid_format'),
            'phone_number|min' => trans('message.txt_invalid_format'),
            'phone_number|unique' => trans('message.txt_invalid_format'),
            'first_name.required' => trans('message.txt_invalid_format'),
            'last_name.required' => trans('message.txt_invalid_format'),
            'province_id.required' => trans('message.txt_invalid_format'),
            'province_id.integer' => trans('message.txt_invalid_format'),
            'codes.required' => trans('message.txt_invalid_format'),
        ];
    }
}
