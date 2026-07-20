<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reported_profile_id' => ['required', 'integer', Rule::exists('sport_profiles', 'id')],
            'reason' => ['required', 'string', 'max:80'],
            'details' => ['nullable', 'string', 'max:2000'],
            'event_conversation_id' => ['nullable', 'integer', Rule::exists('event_conversations', 'id')],
            'event_message_id' => ['nullable', 'integer', Rule::exists('event_messages', 'id')],
            'sport_session_id' => ['nullable', 'integer', Rule::exists('sport_sessions', 'id')],
        ];
    }
}
