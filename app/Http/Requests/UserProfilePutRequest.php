<?php

namespace App\Http\Requests;

use App\Traits\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UserProfilePutRequest extends FormRequest
{
    use Language;

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status'  => 'FAIL',
            'message' => trans('message.txt_data_given_invalid'),
            'errors' => $validator->errors(),
            'error_code' => config('constants.error_code.invalid_data_putuserprofile')
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
        $rule = [
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50'
        ];
        if (!empty($this->get('phone_number'))) {
            $rule['phone_number'] = 'digits:10|regex:' . $this->phoneNumberVN();
        }
        if (!empty($this->get('email'))) {
            $rule['email'] = 'email';
        }
        return $rule;
    }
}
