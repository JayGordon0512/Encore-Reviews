<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\ReviewInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReviewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invitation_token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'display_name' => ['sometimes', 'nullable', 'string'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'content' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $tokenHash = hash('sha256', $validated['invitation_token']);

        $invitation = ReviewInvitation::query()
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($invitation === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid or expired invitation token.',
            ], 422);
        }

        $reviewer = Reviewer::firstOrCreate(
            ['email_hash' => hash('sha256', Str::lower(trim($validated['email'])))],
            ['display_name' => $validated['display_name'] ?? null]
        );

        $review = Review::create([
            'performance_id' => $invitation->performance_id,
            'reviewer_id' => $reviewer->id,
            'rating' => $validated['rating'],
            'would_recommend' => $validated['would_recommend'],
            'tags' => $validated['tags'] ?? null,
            'content' => $validated['content'] ?? null,
            'verified' => true,
            'verification_source' => 'invitation',
            'moderation_status' => 'pending',
            'submitted_at' => now(),
        ]);

        $invitation->used_at = now();
        $invitation->save();

        return response()->json([
            'ok' => true,
            'review' => [
                'id' => $review->id,
                'performance_id' => $review->performance_id,
                'rating' => $review->rating,
                'would_recommend' => $review->would_recommend,
                'submitted_at' => $review->submitted_at,
            ],
        ], 201);
    }
}
