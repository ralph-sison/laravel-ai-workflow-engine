<?php

namespace App\Http\Requests\Api\V1\Connector;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'credentials' => ['sometimes', 'array'],
            'meta'        => ['nullable', 'array'],
            'is_active'   => ['boolean'],
        ];
    }
}
