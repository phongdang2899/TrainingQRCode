<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerSendOTPRequest extends FormRequest
{
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status'  => 'FAIL',
            'message' => trans('message.txt_data_given_invalid'), 
            'errors' => $validator->errors(),
            'error_code' => config('constants.error_code.invalid_data_phoneotp')
        ], Response::HTTP_BAD_REQUEST);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function rules()
    {
        return [
            'codes'   => 'required|array',
            'codes.*'  => 'required|string|regex:/^[A-Za-z0-9._-]+$/',
            'phone_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        ];
    }

    public function messages()
    {
        return [
            'codes.required' => trans('message.txt_invalid_format'),
            'phone_number.min' => trans('message.txt_invalid_format'),
        ];
    }
}
