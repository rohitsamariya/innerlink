<?php

declare(strict_types=1);

namespace App\Domains\Calling\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IceCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'candidate' => ['required', 'array'],
        ];
    }
}
