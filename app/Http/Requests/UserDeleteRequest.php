<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserDeleteRequest extends FormRequest
{
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status'  => 'FAIL',
            'message' => trans('message.txt_data_given_invalid'), 
            'errors' => $validator->errors(),
            'error_code' => config('constants.error_code.invalid_data_deleteusers')
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
        return [
            'ids'   => 'required|array',
            'ids.*' => 'required|integer',
        ];
    }
}
