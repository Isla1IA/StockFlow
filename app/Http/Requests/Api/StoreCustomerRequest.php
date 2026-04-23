<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_code' => ['required', 'string', 'max:20', 'unique:customers,customer_code'],
            'name' => ['required', 'string', 'max:120'],
            'tax_id' => ['nullable', 'string', 'max:30', 'unique:customers,tax_id'],
            'email' => ['nullable', 'email', 'max:120', 'unique:customers,email'],
            'phone' => ['nullable', 'string', 'max:10'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
