<?php

declare(strict_types=1);

namespace App\Domains\Communication\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:200'],
        ];
    }
}
