<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ListAuditLogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action'        => ['sometimes', 'string', 'max:100'],
            'admin_user_id' => ['sometimes', 'integer'],
            'admin_email'   => ['sometimes', 'string', 'max:255'],
            'target_type'   => ['sometimes', 'string', 'max:50'],
            'target_id'     => ['sometimes', 'integer'],
            'from'          => ['sometimes', 'date'],
            'to'            => ['sometimes', 'date', 'after_or_equal:from'],
            'per_page'      => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }
}
