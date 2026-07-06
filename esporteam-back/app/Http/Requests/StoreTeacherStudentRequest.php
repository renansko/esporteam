<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherStudentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'student_profile_id' => ['required', 'integer', Rule::exists('sport_profiles', 'id')],
            'status' => ['nullable', Rule::in(['active', 'pending'])],
        ];
    }
}
