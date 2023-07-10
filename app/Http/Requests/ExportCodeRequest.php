<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportCodeRequest extends FormRequest
{
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
            'campaign_id' => 'required|integer',
            'type' => 'required|integer|min:1',
        ];
        if (!empty($this->get('per_page'))) {
            $rule['per_page'] = 'integer';
        }
        if (!empty($this->get('page'))) {
            $rule['page'] = 'integer';
        }
        return $rule;
    }
}
