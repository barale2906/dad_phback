<?php

namespace App\Http\Requests\Barcode;

use Illuminate\Foundation\Http\FormRequest;

class PrintBarcodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'inicio' => ['required', 'integer', 'min:1'],
            'cantidad' => ['required', 'integer', 'min:1'],
            'repeticiones' => ['required', 'integer', 'min:1'],
        ];
    }
}
