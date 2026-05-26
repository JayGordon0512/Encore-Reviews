<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Reviewer;
use App\Models\ReviewInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $emailHash = hash('sha256', Str::lower(trim($validated['email'])));
        $tokenHash = hash('sha256', $validated['invitation_token']);

        $result = DB::transaction(function () use ($emailHash, $tokenHash, $validated): array {
            $invitation = ReviewInvitation::query()
                ->where('token_hash', $tokenHash)
                ->whereNull('used_at')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->lockForUpdate()
                ->first();

            if ($invitation === null) {
                return ['error' => 'invalid_token'];
            }

            if ($invitation->email_hash !== null && ! hash_equals($invitation->email_hash, $emailHash)) {
                return ['error' => 'email_mismatch'];
            }

            $reviewer = Reviewer::firstOrCreate(
                ['email_hash' => $emailHash],
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

            return ['review' => $review];
        });

        if (($result['error'] ?? null) === 'invalid_token') {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid or expired invitation token.',
            ], 422);
        }

        if (($result['error'] ?? null) === 'email_mismatch') {
            return response()->json([
                'ok' => false,
                'message' => 'Invitation token does not match this email address.',
            ], 422);
        }

        $review = $result['review'];

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
