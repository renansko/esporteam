<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteConversationMediaUploadRequest extends FormRequest
{
    public function rules(): array { return ['upload_id' => ['required', 'uuid']]; }
}
