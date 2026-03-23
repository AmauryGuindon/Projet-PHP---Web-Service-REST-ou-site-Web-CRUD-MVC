<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'match_id'          => ['required', 'string'],
            'amount'            => ['required', 'numeric', 'min:1', 'max:10000'],
            'predicted_outcome' => ['required', 'in:home_win,draw,away_win'],
        ];
    }
}
