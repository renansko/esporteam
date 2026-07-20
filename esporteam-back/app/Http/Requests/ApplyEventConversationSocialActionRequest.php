<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyEventConversationSocialActionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['reply', 'mention', 'reaction', 'read', 'mute', 'typing', 'remove', 'hide', 'mute_profile', 'ban', 'announce'])],
            'message_id' => ['nullable', 'integer', 'min:1'],
            'body' => ['nullable', 'string', 'max:2000'],
            'client_message_id' => ['nullable', 'uuid'],
            'mentioned_profile_id' => ['nullable', 'integer', 'min:1'],
            'emoji' => ['nullable', 'string', Rule::in(['👍', '❤️', '😂', '🎉', '👀'])],
            'active' => ['nullable', 'boolean'],
            'cursor' => ['nullable', 'integer', 'min:0'],
            'muted' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:240'],
            'target_profile_id' => ['nullable', 'integer', 'exists:sport_profiles,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
