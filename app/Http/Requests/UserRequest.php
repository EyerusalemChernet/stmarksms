<?php

namespace App\Http\Requests;

use App\Helpers\Qs;
use Illuminate\Foundation\Http\FormRequest;

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
            'phone'     => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'phone2'    => ['sometimes', 'nullable', 'regex:/^09[0-9]{8}$/'],
            'email'     => 'sometimes|nullable|email|max:100|unique:users',
            'username'  => 'sometimes|nullable|alpha_dash|min:8|max:100|unique:users',
            'photo'     => 'sometimes|nullable|image|mimes:jpeg,gif,png,jpg|max:2048',
            'address'   => 'required|string|min:6|max:120',
            'state_id'  => 'required|exists:states,id',
            'lga_id'    => 'required|exists:lgas,id',
            'nal_id'    => 'required',
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
            'nal_id'   => 'required',
        ];
        return ($this->method() === 'POST') ? $store : $update;
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
            'password.regex' => 'Password must be at least 8 characters with at least 1 uppercase letter and 1 number.',
            'phone.regex'    => 'Phone number must be 10 digits starting with 09 (e.g. 0911434321).',
            'phone2.regex'   => 'Alternative phone must be 10 digits starting with 09 (e.g. 0911434321).',
        ];
    }

    protected function getValidatorInstance()
    {
        if($this->method() === 'POST'){
            $input = $this->all();

            $input['user_type'] = Qs::decodeHash($input['user_type']);

            $this->getInputSource()->replace($input);

        }

        if($this->method() === 'PUT'){
            $this->user = Qs::decodeHash($this->user);
        }

        return parent::getValidatorInstance();

    }
}
