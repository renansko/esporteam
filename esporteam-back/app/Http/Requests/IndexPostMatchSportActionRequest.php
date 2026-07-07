<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexPostMatchSportActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'connection_id' => ['nullable', 'required_without:session_id', 'prohibits:session_id', 'integer', Rule::exists('connections', 'id')],
            'session_id' => ['nullable', 'required_without:connection_id', 'integer', Rule::exists('sport_sessions', 'id')],
        ];
    }
}
