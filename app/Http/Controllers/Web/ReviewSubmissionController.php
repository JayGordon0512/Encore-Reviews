<?php

namespace App\Http\Controllers\Web;

use App\Application\Reviews\SubmitReviewService;
use App\Domain\Invitations\InvitationToken;
use App\Http\Controllers\Controller;
use App\Models\ReviewInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewSubmissionController extends Controller
{
    private const SESSION_KEY = 'review_invitation.id';

    public function __construct(
        private readonly InvitationToken $tokens,
        private readonly SubmitReviewService $reviews,
    ) {}

    public function entry()
    {
        return response()->view('public.review-invitation-entry')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function exchange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invitation_token' => ['required', 'string', 'max:512'],
        ]);
        $invitation = ReviewInvitation::query()
            ->available()
            ->whereIn('token_hash', $this->tokens->lookupDigests($validated['invitation_token']))
            ->first();

        if ($invitation === null) {
            return response()->json([
                'ok' => false,
                'message' => 'This review invitation is unavailable.',
            ], 422);
        }

        $request->session()->migrate(true);
        $request->session()->put(self::SESSION_KEY, $invitation->id);

        return response()->json([
            'ok' => true,
            'redirect' => route('review.submit'),
        ]);
    }

    public function show(Request $request)
    {
        $invitationId = (string) $request->session()->get(self::SESSION_KEY, '');
        $invitation = $invitationId !== ''
            ? ReviewInvitation::query()->available()->with('performance.show')->find($invitationId)
            : null;

        if ($invitation === null) {
            $request->session()->forget(self::SESSION_KEY);

            return response()->view('public.review-invitation-unavailable', status: 404)
                ->header('Referrer-Policy', 'no-referrer')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return response()->view('public.review-submit', [
            'invitation' => $invitation,
        ])->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'display_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'would_recommend' => ['required', 'boolean'],
            'tags' => ['sometimes', 'array', 'max:12'],
            'tags.*' => ['string', 'max:50'],
            'content' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ]);
        $invitationId = (string) $request->session()->get(self::SESSION_KEY, '');
        $result = $invitationId === ''
            ? ['error' => 'invalid_token']
            : $this->reviews->usingInvitationId($invitationId, $validated);

        if (($result['error'] ?? null) === 'invalid_token') {
            $request->session()->forget(self::SESSION_KEY);

            return response()->json(['ok' => false, 'message' => 'Invalid or expired invitation.'], 422);
        }
        if (($result['error'] ?? null) === 'email_mismatch') {
            return response()->json(['ok' => false, 'message' => 'Invitation does not match this email address.'], 422);
        }
        if (($result['error'] ?? null) === 'reviews_locked') {
            $request->session()->forget(self::SESSION_KEY);

            return response()->json(['ok' => false, 'message' => 'Reviews are closed for this historical show.'], 422);
        }

        $review = $result['review'];
        $request->session()->forget(self::SESSION_KEY);

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
