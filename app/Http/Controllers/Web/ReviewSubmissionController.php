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
                ->first()
            : null;

        return view('public.review-submit', [
            'invitationToken' => $invitationToken,
            'invitation' => $invitation,
        ]);
    }
}
