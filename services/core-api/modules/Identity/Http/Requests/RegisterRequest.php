<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email:rfc', 'max:320'],
            // Length over composition rules: composition pushes people toward
            // predictable transformations of short passwords.
            'password' => ['required', 'string', 'min:'.config('kms.accounts.password_min_length'), 'max:4096'],
            'profile_name' => ['required', 'string', 'min:1', 'max:50'],
            'locale' => ['sometimes', 'string', 'max:20'],
            'accepts_terms' => ['accepted'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
