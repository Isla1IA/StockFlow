<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->getKey();

        return [
            'customer_code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('customers', 'customer_code')->ignore($customerId),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'tax_id' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('customers', 'tax_id')->ignore($customerId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:120',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'phone' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
