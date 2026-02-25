<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
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
            'sport_id' => ['required', 'integer', 'exists:sports,id'],
            'name' => ['required', 'string', 'max:120', Rule::unique('teams', 'name')->where('sport_id', $this->sport_id)],
            'short_name' => ['required', 'string', 'max:16'],
            'country' => ['required', 'string', 'max:80'],
        ];
    }
}
