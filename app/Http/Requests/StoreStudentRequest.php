<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->role === User::ROLE_ADMIN;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['nullable', 'string', 'max:32'],
            'school_class_id' => ['required', 'integer', Rule::exists('school_classes', 'id')],
            'class' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'admission_id' => ['nullable', 'string', 'max:255'],
            'auto_generate' => ['nullable', 'boolean'],
            'password' => ['required_unless:auto_generate,1', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'auto_generate' => $this->boolean('auto_generate') ? 1 : 0,
        ]);
    }
}
