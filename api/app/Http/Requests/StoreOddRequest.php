<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOddRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id'  => ['required', 'string'],
            'home_win'  => ['required', 'numeric', 'min:1.01'],
            'draw'      => ['required', 'numeric', 'min:1.01'],
            'away_win'  => ['required', 'numeric', 'min:1.01'],
            'bookmaker' => ['required', 'string', 'max:100'],
            'source'    => ['nullable', 'in:internal,external'],
        ];
    }
}
