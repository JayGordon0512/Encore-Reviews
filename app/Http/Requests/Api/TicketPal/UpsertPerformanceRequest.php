<?php

namespace App\Http\Requests\Api\TicketPal;

use Illuminate\Foundation\Http\FormRequest;

class UpsertPerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'provider_event_id' => ['required', 'string', 'max:255'],
            'provider_performance_id' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'venue_name' => ['required', 'string', 'max:255'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
            'status' => ['sometimes', 'nullable', 'string', 'max:255'],
            'venue_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'venue_postcode' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
