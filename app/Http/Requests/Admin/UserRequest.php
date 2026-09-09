<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = is_object($this->route('user')) ? $this->route('user')->id : $this->route('user');

        return [
            'username' => [
                'required', 'string', 'max:50',
                Rule::unique('users', 'username')->ignore($userId),
                'regex:/^[a-z0-9_]+$/',
            ],
            'name' => ['required', 'string', 'max:200'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:200',
                'email:rfc',
                'regex:/^(?=.{3,}@)[a-zA-Z0-9]+([._%+-][a-zA-Z0-9]+)*@((gmail\.com|yahoo\.com|outlook\.com)|([a-zA-Z0-9]{2,}(\.[a-zA-Z0-9]{2,})*\.(co\.id|ac\.id|or\.id|go\.id|sch\.id|web\.id|mil\.id|biz\.id|my\.id|net\.id|id)))$/i',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => $userId ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^08[0-9]{10,11}$/',
            ],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'username.regex' => 'Username hanya boleh berisi huruf kecil, angka, dan underscore.',
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan email seperti nama@gmail.com atau nama@perusahaan.co.id.',
            'email.regex' => 'Format email tidak valid. Gunakan email seperti nama@gmail.com atau nama@perusahaan.co.id.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'role.exists' => 'Role tidak valid.',
            'phone.regex' => 'Nomor telepon harus terdiri dari 12–13 digit angka.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email') && is_string($this->email)) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
        if ($this->has('phone') && is_string($this->phone)) {
            $cleanPhone = trim($this->phone);
            $this->merge(['phone' => $cleanPhone !== '' ? $cleanPhone : null]);
        }
    }
}