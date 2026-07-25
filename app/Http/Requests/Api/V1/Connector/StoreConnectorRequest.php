<?php

namespace App\Http\Requests\Api\V1\Connector;

use Illuminate\Foundation\Http\FormRequest;

class StoreConnectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'type'        => ['required', 'string', 'in:openai,claude,ollama,slack,webhook'],
            'credentials' => ['required', 'array'],
            'meta'        => ['nullable', 'array'],
            'is_active'   => ['boolean'],
        ];
    }
}
