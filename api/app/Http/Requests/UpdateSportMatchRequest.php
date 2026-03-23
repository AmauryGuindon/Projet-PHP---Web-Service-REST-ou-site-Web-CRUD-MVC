<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSportMatchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sport_id' => ['sometimes', 'required', 'string', Rule::exists('mongodb.sports', '_id')],
            'home_team_id' => ['sometimes', 'required', 'string', Rule::exists('mongodb.teams', '_id'), 'different:away_team_id'],
            'away_team_id' => ['sometimes', 'required', 'string', Rule::exists('mongodb.teams', '_id')],
            'starts_at' => ['sometimes', 'required', 'date'],
            'status' => ['sometimes', 'in:scheduled,live,finished,cancelled'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
