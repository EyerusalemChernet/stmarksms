<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class ExamCreate extends FormRequest
{

    public function authorize()
    {
        return true;
    }


    public function rules()
    {
        return [
            'name'        => 'required|string|max:100',
            'term'        => 'required|integer|in:1,2',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
            'status'      => 'nullable|in:upcoming,ongoing,completed,cancelled',
        ];
    }

}
