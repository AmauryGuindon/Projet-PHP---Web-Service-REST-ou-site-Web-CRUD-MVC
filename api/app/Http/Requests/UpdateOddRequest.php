<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'home_win'  => ['sometimes', 'numeric', 'min:1.01'],
            'draw'      => ['sometimes', 'numeric', 'min:1.01'],
            'away_win'  => ['sometimes', 'numeric', 'min:1.01'],
            'bookmaker' => ['sometimes', 'string', 'max:100'],
            'source'    => ['sometimes', 'in:internal,external'],
        ];
    }
}
