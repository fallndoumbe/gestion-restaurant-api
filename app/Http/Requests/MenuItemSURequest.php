<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MenuItemSURequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:140',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'is_available' => 'boolean',
            'preparation_time' => 'integer|min:1|max:240',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity_needed' => 'required|numeric|min:0.001',
        ];
    }
}
