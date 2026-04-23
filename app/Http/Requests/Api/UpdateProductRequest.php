<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->getKey();

        return [
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:64',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'min_stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
