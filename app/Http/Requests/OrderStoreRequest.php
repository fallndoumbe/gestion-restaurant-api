<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use App\Models\Table;
use Illuminate\Foundation\Http\FormRequest;

class OrderStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.menu_item_id' => 'required|exists:menu_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.special_notes' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500'
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $table = Table::find($this->table_id);

            if ($table && !in_array($table->status, ['free', 'occupied'], true)) {
                $validator->errors()->add('table_id', "Cette table n'est pas disponible");
            }

            foreach ((array)$this->items as $index => $item) {
                $menuItem = MenuItem::find($item['menu_item_id'] ?? null);
                if ($menuItem && !$menuItem->is_available) {
                    $validator->errors()->add("items.$index.menu_item_id", "{$menuItem->name} n'est pas disponible");
                }
            }
        });
    }
}
