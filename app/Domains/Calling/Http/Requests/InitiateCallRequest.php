<?php

declare(strict_types=1);

namespace App\Domains\Calling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateCallRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id', 'different:caller_id'],
        ];
    }

    public function messages(): array
    {
        return [
            'receiver_id.different' => 'You cannot call yourself.',
        ];
    }
}
