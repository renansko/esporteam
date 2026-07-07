<?php

namespace App\Http\Requests;

use App\Enums\SportSessionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostMatchSportActionSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'connection_id' => ['nullable', 'required_without:session_id', 'prohibits:session_id', 'integer', Rule::exists('connections', 'id')],
            'session_id' => ['nullable', 'required_without:connection_id', 'integer', Rule::exists('sport_sessions', 'id')],
            'sport_id' => ['nullable', 'integer', Rule::exists('sports', 'id')->where('is_active', true)],
            'title' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['nullable', Rule::in(SportSessionType::values())],
            'starts_at' => ['required', 'date'],
            'location_label' => ['nullable', 'required_without_all:city,region,latitude_approx,longitude_approx', 'string', 'max:160'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'latitude_approx' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude_approx'],
            'longitude_approx' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude_approx'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'price_cents' => ['prohibited'],
            'fee_cents' => ['prohibited'],
            'is_paid' => ['prohibited'],
            'payment_required' => ['prohibited'],
            'currency' => ['prohibited'],
        ];
    }
}
