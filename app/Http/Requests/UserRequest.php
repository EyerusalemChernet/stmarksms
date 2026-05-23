<?php

namespace App\Http\Requests;

use App\Helpers\Qs;
use App\Repositories\UserRepo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UserRequest extends FormRequest
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
        $store =  [
            'name'      => 'required|string|min:6|max:150',
            'password'  => ['nullable', 'string', 'min:8', 'regex:/^(?=.*[A-Z])(?=.*\d).+$/'],
            'user_type' => 'required',
            'gender'    => 'required|string',
            'phone'     => ['required', 'regex:/^09[0-9]{8}$/'],
            'phone2'    => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'email'     => 'required|email|max:100|unique:users',
            'username'  => 'sometimes|nullable|alpha_dash|min:8|max:100|unique:users',
            'photo'     => 'required|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address'   => 'required|string|min:6|max:120',
            'state_id'  => 'required|exists:states,id',
            'lga_id'    => 'required|exists:lgas,id',
            'nal_id'         => 'required',
            'department_id'  => 'nullable|exists:departments,id',
            'employee_id'    => 'sometimes|nullable|integer|exists:employees,id',
        ];
        $update =  [
            'name'     => 'required|string|min:6|max:150',
            'gender'   => 'required|string',
            'phone'    => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'phone2'   => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'email'    => 'sometimes|nullable|email|max:100|unique:users,email,'.$this->user,
            'photo'    => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address'  => 'required|string|min:6|max:120',
            'state_id' => 'required|exists:states,id',
            'lga_id'   => 'required|exists:lgas,id',
            'nal_id'        => 'required',
            'department_id' => 'nullable|exists:departments,id',
        ];
        return ($this->method() === 'POST') ? $store : $update;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($this->method() !== 'POST' || !$this->filled('user_type')) {
                return;
            }

            $type = app(UserRepo::class)->findType($this->user_type);
            if ($type && $type->title === 'teacher' && !$this->department_id) {
                $v->errors()->add('department_id', 'Please select a department for the teacher.');
            }

            if ($this->filled('employee_id')) {
                $employee = \App\Models\Employee::find($this->employee_id);
                if (!$employee || $employee->user_id) {
                    $v->errors()->add('employee_id', 'This employee already has a user account or does not exist.');
                }
            }
        });
    }

    public function attributes()
    {
        return  [
            'nal_id'    => 'Nationality',
            'state_id'  => 'Region',
            'lga_id'    => 'Sub-city / Woreda',
            'user_type' => 'User Type',
            'phone'     => 'Phone Number',
            'phone2'    => 'Alternative Phone',
            'password'  => 'Password',
        ];
    }

    public function messages()
    {
        return [
            'phone.required'  => 'Phone number is required.',
            'phone.regex'     => 'Phone number must be 10 digits starting with 09 (e.g. 0911434321).',
            'phone2.regex'    => 'Alternative phone must be 10 digits starting with 09 (e.g. 0911434321).',
            'email.required'  => 'Email address is required.',
            'photo.required'  => 'A passport/profile photo is required.',
        ];
    }

    protected function getValidatorInstance()
    {
        if($this->method() === 'POST'){
            $input = $this->all();

            $input['user_type'] = Qs::decodeHash($input['user_type']);

            if (!empty($input['employee_id'])) {
                $decodedEmp = Qs::decodeHash($input['employee_id']);
                if ($decodedEmp) {
                    $input['employee_id'] = $decodedEmp;
                }
            }

            $this->getInputSource()->replace($input);

        }

        if($this->method() === 'PUT'){
            $this->user = Qs::decodeHash($this->user);
        }

        return parent::getValidatorInstance();

    }
}
