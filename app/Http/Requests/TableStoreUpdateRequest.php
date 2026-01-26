<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TableStoreUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'number' => 'required|integer|min:1|unique:tables,number,' . ($this->route('table') ?? 'NULL'),
            'capacity' => 'required|integer|min:1|max:100',
            'location' => 'nullable|string|max:120',
            'status' => 'nullable|in:free,occupied,reserved,out_of_service',
        ];
    }
}
