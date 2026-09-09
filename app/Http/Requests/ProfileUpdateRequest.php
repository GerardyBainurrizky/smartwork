<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name' => ['required', 'string', 'max:200'],
            'username' => ['required', 'string', 'max:50', Rule::unique(User::class)->ignore($user?->id)],
            'email' => [
                'required',
                'string',
                'lowercase',
                'max:200',
                'email:rfc',
                'regex:/^(?=.{3,}@)[a-zA-Z0-9]+([._%+-][a-zA-Z0-9]+)*@((gmail\.com|yahoo\.com|outlook\.com)|([a-zA-Z0-9]{2,}(\.[a-zA-Z0-9]{2,})*\.(co\.id|ac\.id|or\.id|go\.id|sch\.id|web\.id|mil\.id|biz\.id|my\.id|net\.id|id)))$/i',
                Rule::unique(User::class)->ignore($user?->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^08[0-9]{10,11}$/',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan oleh pengguna lain.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid. Gunakan email seperti nama@gmail.com atau nama@perusahaan.co.id.',
            'email.regex' => 'Format email tidak valid. Gunakan email seperti nama@gmail.com atau nama@perusahaan.co.id.',
            'email.unique' => 'Email sudah digunakan oleh pengguna lain.',
            'phone.regex' => 'Nomor telepon harus terdiri dari 12–13 digit angka.',
        ];
    }
}