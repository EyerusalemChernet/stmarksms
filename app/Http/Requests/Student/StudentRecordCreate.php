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
            // Date of birth: student must be at least 3 and at most 25 years old
            'dob'           => [
                'required', 'date',
                'before:' . now()->subYears(3)->format('Y-m-d'),
                'after:'  . now()->subYears(25)->format('Y-m-d'),
            ],
            // Ethiopian mobile: 10 digits starting with 09
            'phone'         => ['sometimes', 'nullable', 'string', 'min:7', 'max:20'],
            // Alternative phone — optional
            'phone2'        => ['sometimes', 'nullable', 'string', 'min:7', 'max:20'],
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
            'dob'          => 'Date of Birth',
        ];
    }

    public function messages()
    {
        return [
            'dob.before'   => 'Student must be at least 3 years old.',
            'dob.after'    => 'Student age cannot exceed 25 years. Please verify the date of birth.',
            'dob.required' => 'Date of Birth is required.',
            'dob.date'     => 'Date of Birth must be a valid date.',
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
