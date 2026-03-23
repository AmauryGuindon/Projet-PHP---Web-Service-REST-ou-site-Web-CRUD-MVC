<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount'            => ['sometimes', 'numeric', 'min:1', 'max:10000'],
            'predicted_outcome' => ['sometimes', 'in:home_win,draw,away_win'],
        ];
    }
}
