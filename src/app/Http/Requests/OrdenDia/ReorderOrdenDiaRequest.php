<?php

namespace App\Http\Requests\OrdenDia;

use Illuminate\Foundation\Http\FormRequest;

class ReorderOrdenDiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:orden_dia_items,id'],
            'items.*.orden' => ['required', 'integer', 'min:1'],
        ];
    }
}
