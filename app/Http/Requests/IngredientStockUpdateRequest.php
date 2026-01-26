<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngredientStockUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'quantity' => 'required|numeric|min:0.01',
            'operation' => 'required|in:add,remove',
        ];
    }
}
