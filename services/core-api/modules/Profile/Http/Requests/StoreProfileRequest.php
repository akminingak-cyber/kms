<?php

declare(strict_types=1);

namespace Modules\Profile\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:1', 'max:50'],
            'locale' => ['sometimes', 'string', 'max:20'],
            'max_rating' => ['sometimes', 'nullable', 'string', 'max:20'],
            'pin' => ['sometimes', 'nullable', 'digits:'.config('kms.profiles.pin_length')],
        ];
    }
}
