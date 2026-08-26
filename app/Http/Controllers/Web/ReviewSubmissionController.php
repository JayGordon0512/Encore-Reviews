<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ReviewInvitation;
use Illuminate\Http\Request;

class ReviewSubmissionController extends Controller
{
    public function show(Request $request)
    {
        $invitationToken = (string) $request->query('token', $request->query('invitation_token', ''));
        $invitation = $invitationToken !== ''
            ? ReviewInvitation::query()
                ->with('performance.show')
                ->where('token_hash', hash('sha256', $invitationToken))
                ->whereNotNull('sent_at')
                ->whereNull('used_at')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first()
            : null;

        if ($invitation === null) {
            return response()->view('public.review-invitation-unavailable', status: 404);
        }

        return view('public.review-submit', [
            'invitationToken' => $invitationToken,
            'invitation' => $invitation,
        ]);
    }
}
