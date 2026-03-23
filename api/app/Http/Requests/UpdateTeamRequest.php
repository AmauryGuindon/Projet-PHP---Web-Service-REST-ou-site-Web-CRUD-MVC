<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTeamRequest extends FormRequest
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
        $teamId = $this->route('team')->id;
        $sportId = $this->input('sport_id', $this->route('team')->sport_id);

        return [
            'sport_id' => ['sometimes', 'required', 'string', Rule::exists('mongodb.sports', '_id')],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'short_name' => ['sometimes', 'required', 'string', 'max:16'],
            'country' => ['sometimes', 'required', 'string', 'max:80'],
        ];
    }
}
