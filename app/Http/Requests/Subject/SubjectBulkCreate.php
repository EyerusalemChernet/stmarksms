<?php

namespace App\Http\Requests\Subject;

use Illuminate\Foundation\Http\FormRequest;

class SubjectBulkCreate extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'           => 'required|string|min:2|max:100',
            'slug'           => 'required|string|min:2|max:20',
            'department_id'  => 'required|exists:departments,id',
            'my_class_ids'   => 'required|array|min:1',
            'my_class_ids.*' => 'integer|exists:my_classes,id',
        ];
    }

    public function attributes()
    {
        return [
            'my_class_ids'   => 'Classes',
            'department_id'  => 'Department',
            'slug'           => 'Short Name',
        ];
    }
}
