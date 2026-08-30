<?php

namespace App\Http\Controllers\Api;

use App\Application\Reviews\SubmitReviewService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private readonly SubmitReviewService $reviews) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invitation_token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
            'tags' => ['sometimes', 'array', 'max:12'],
            'tags.*' => ['string', 'max:50'],
            'content' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);

        $result = $this->reviews->usingToken($validated['invitation_token'], $validated);

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

        if (($result['error'] ?? null) === 'reviews_locked') {
            return response()->json([
                'ok' => false,
                'message' => 'Reviews are closed for this historical show.',
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
