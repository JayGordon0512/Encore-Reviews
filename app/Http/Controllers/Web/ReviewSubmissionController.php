<?php

namespace App\Http\Controllers\Web;

use App\Domain\Invitations\InvitationToken;
use App\Http\Controllers\Controller;
use App\Models\ReviewInvitation;
use Illuminate\Http\Request;

class ReviewSubmissionController extends Controller
{
    public function __construct(private readonly InvitationToken $tokens) {}

    public function show(Request $request)
    {
        $invitationToken = (string) $request->query('token', $request->query('invitation_token', ''));
        $invitation = $invitationToken !== ''
            ? ReviewInvitation::query()
                ->with('performance.show')
                ->whereIn('token_hash', $this->tokens->lookupDigests($invitationToken))
                ->whereNotNull('sent_at')
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first()
            : null;

        if ($invitation === null) {
            return response()->view('public.review-invitation-unavailable', status: 404)
                ->header('Referrer-Policy', 'no-referrer')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return response()->view('public.review-submit', [
            'invitationToken' => $invitationToken,
            'invitation' => $invitation,
        ])->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
