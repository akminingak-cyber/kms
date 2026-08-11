<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Device\Contracts\DeviceClasses;

final class LoginRequest extends FormRequest
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
            'password' => ['required', 'string', 'max:4096'],
            /*
             * Television classes are rejected here on purpose: a TV has no
             * usable keyboard and must use the activation flow. Accepting a
             * password login from a TV class would create a second, weaker path
             * to the same session.
             */
            'device_class' => ['required', 'string', Rule::in(DeviceClasses::passwordCapable())],
            'device_name' => ['required', 'string', 'min:1', 'max:100'],
            'device_fingerprint' => ['sometimes', 'nullable', 'string', 'max:512'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
