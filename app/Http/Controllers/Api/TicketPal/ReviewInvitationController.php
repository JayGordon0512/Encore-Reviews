<?php

namespace App\Http\Controllers\Api\TicketPal;

use App\Http\Controllers\Controller;
use App\Models\Performance;
use App\Models\ReviewInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewInvitationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'performance_id' => ['required', 'string', 'exists:performances,id'],
            'email' => ['required', 'email'],
            'provider_source' => ['sometimes', 'string'],
            'provider_booking_id' => ['sometimes', 'nullable', 'string'],
            'provider_ticket_id' => ['sometimes', 'nullable', 'string'],
            'attendance_state' => ['sometimes', 'nullable', 'string'],
            'sent_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
            'meta' => ['sometimes', 'nullable', 'array'],
        ]);

        $token = Str::random(48);
        $invitation = ReviewInvitation::create([
            'performance_id' => $validated['performance_id'],
            'email_hash' => hash('sha256', Str::lower(trim($validated['email']))),
            'token_hash' => hash('sha256', $token),
            'sent_at' => $validated['sent_at'] ?? now(),
            'expires_at' => $validated['expires_at'] ?? now()->addDays(7),
            'provider_source' => $validated['provider_source'] ?? 'ticketpal',
            'provider_booking_id' => $validated['provider_booking_id'] ?? null,
            'provider_ticket_id' => $validated['provider_ticket_id'] ?? null,
            'attendance_state' => $validated['attendance_state'] ?? null,
            'meta' => $validated['meta'] ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'invitation' => [
                'id' => $invitation->id,
                'performance_id' => $invitation->performance_id,
                'sent_at' => $invitation->sent_at,
                'expires_at' => $invitation->expires_at,
                'token' => $token,
            ],
        ], 201);
    }
}
