<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;
use App\Helpers\Qs;

class StudentRecordUpdate extends FormRequest
{

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
            'name'        => 'required|string|min:6|max:150',
            'gender'      => 'required|string',
            // Date of birth: student must be at least 3 and at most 25 years old
            'dob'         => [
                'sometimes', 'nullable', 'date',
                'before:' . now()->subYears(3)->format('Y-m-d'),
                'after:'  . now()->subYears(25)->format('Y-m-d'),
            ],
            'phone'       => 'sometimes|nullable|string|min:6|max:20',
            'email'       => 'sometimes|nullable|email|max:100|unique:users,id',
            'photo'       => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address'     => 'required|string|min:6|max:120',
            'bg_id'       => 'sometimes|nullable',
            'my_class_id' => 'required',
            'section_id'  => 'required',
            'state_id'    => 'required',
            'lga_id'      => 'required',
            'nal_id'      => 'required',
            'my_parent_id'=> 'sometimes|nullable',
        ];
    }

    public function attributes()
    {
        return [
            'nal_id'       => 'Nationality',
            'state_id'     => 'Region',
            'lga_id'       => 'Sub-city / Woreda',
            'bg_id'        => 'Blood Group',
            'my_parent_id' => 'Parent',
            'my_class_id'  => 'Class',
            'section_id'   => 'Section',
            'dob'          => 'Date of Birth',
        ];
    }

    public function messages()
    {
        return [
            'dob.before' => 'Student must be at least 3 years old.',
            'dob.after'  => 'Student age cannot exceed 25 years. Please verify the date of birth.',
        ];
    }

    protected function getValidatorInstance()
    {
        $input = $this->all();

        $input['my_parent_id'] = $input['my_parent_id'] ? Qs::decodeHash($input['my_parent_id']) : NULL;

        $this->getInputSource()->replace($input);

        return parent::getValidatorInstance();
    }
}
