<?php

namespace App\Http\Requests;

use App\Support\UserRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('user')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $target = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($target?->id),
            ],
            // Kata sandi opsional saat menyunting; hanya diganti bila diisi.
            'password' => ['nullable', 'string', Password::min(8), 'confirmed'],
            'role' => ['required', Rule::in(UserRoles::ALL)],
        ];
    }
}
