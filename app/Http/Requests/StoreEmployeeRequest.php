<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // TODO: replace with a real admin/policy check
    }

    public function rules(): array
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:employees,email'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{7,20}$/'],
            'salary_month' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'salary_period' => ['nullable', 'date_format:Y-m'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ];

        $lookups = ['branch' => 'branches', 'department' => 'departments', 'job_title' => 'job_titles'];

        foreach ($lookups as $field => $table) {
            $rules[$field] = [
                'required',
                $this->input($field) === '__new__' ? 'in:__new__' : "exists:{$table},id",
            ];
            $rules["new_{$field}"] = ["required_if:{$field},__new__", 'nullable', 'string', 'max:100'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'full_name' => 'full name',
            'salary_month' => 'monthly salary',
            'new_branch' => 'new branch name',
            'new_department' => 'new department name',
            'new_job_title' => 'new job title',
        ];
    }

    public function messages(): array
    {
        return [
            'image.max' => 'The profile image must not be larger than 2 MB.',
            'phone.regex' => 'Please enter a valid phone number.',
        ];
    }
}
