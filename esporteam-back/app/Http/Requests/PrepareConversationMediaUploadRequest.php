<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PrepareConversationMediaUploadRequest extends FormRequest
{
    public function rules(): array { return ['mime' => ['required', 'in:image/jpeg,image/png,image/webp']]; }
}
