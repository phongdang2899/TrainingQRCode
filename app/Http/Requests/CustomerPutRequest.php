<?php

namespace App\Http\Requests;

use App\Traits\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CustomerPutRequest extends FormRequest
{
    use Language;

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'status'  => 'FAIL',
            'message' => trans('message.txt_data_given_invalid'), 
            'errors' => $validator->errors(),
            'error_code' => config('constants.error_code.invalid_data_putcustomers')
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
            'phone_number' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10|unique:customers,phone_number,' . $this->id,
            'first_name' => 'required|' . $this->characterVN(),
            'last_name' => 'required|' . $this->characterVN(),
            'gender' => 'required|integer',
            'address' => 'required|string',
            'province_id' => 'required|integer',
            'id_card_number' => 'required|integer|unique:customers,id_card_number,' . $this->id,
        ];
        if (!empty($this->get('brand_name'))) {
            $rule['brand_name'] = 'string';
        }
        if (!empty($this->get('status'))) {
            $rule['status'] = 'integer';
        }
        return $rule;
    }
}
