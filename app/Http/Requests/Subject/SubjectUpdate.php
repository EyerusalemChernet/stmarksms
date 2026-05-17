<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class SubjectUpdate extends FormRequest
{

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|min:3',
            'my_class_id' => 'required',
            'department_id' => 'required|exists:departments,id',
            'slug' => 'nullable|string|min:3',
        ];
    }

    public function attributes()
    {
        return  [
            'my_class_id' => 'Class',
            'department_id' => 'Department',
            'slug' => 'Short Name',
        ];
    }
}
