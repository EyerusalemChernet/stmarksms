<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Qs;

class StudentRecordCreate extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'          => 'required|string|min:6|max:150',
            'gender'        => 'required|string',
            'year_admitted' => 'required|string',
            // Ethiopian mobile: 10 digits starting with 09
            'phone'         => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            // Alternative phone (Guardian 2) — optional, same format
            'phone2'        => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'email'         => 'sometimes|nullable|email|max:100|unique:users',
            'photo'         => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address'       => 'required|string|min:6|max:120',
            'bg_id'         => 'sometimes|nullable',
            'state_id'      => 'required',
            'lga_id'        => 'required',
            'nal_id'        => 'required',
            'my_class_id'   => 'required',
            'section_id'    => 'required',
            'my_parent_id'  => 'sometimes|nullable',
            'religion'      => 'sometimes|nullable|string|max:50',
        ];
    }

    public function attributes()
    {
        return [
            'section_id'   => 'Section',
            'nal_id'       => 'Nationality',
            'my_class_id'  => 'Class',
            'state_id'     => 'Region',
            'lga_id'       => 'Sub-city / Woreda',
            'bg_id'        => 'Blood Group',
            'my_parent_id' => 'Parent',
            'phone'        => 'Phone Number',
            'phone2'       => 'Alternative Phone',
        ];
    }

    public function messages()
    {
        return [
            'phone.regex'  => 'Phone number must be 10 digits starting with 09 (e.g., 0911434321).',
            'phone2.regex' => 'Alternative phone must be 10 digits starting with 09 (e.g., 0911434321).',
        ];
    }

    protected function getValidatorInstance()
    {
        $input = $this->all();
        $input['my_parent_id'] = $input['my_parent_id'] ? Qs::decodeHash($input['my_parent_id']) : null;
        $this->getInputSource()->replace($input);
        return parent::getValidatorInstance();
    }
}
