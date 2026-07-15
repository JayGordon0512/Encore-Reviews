<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewModerationController extends Controller
{
    public function update(Request $request, Review $review): RedirectResponse
    {
        abort_if($request->user()->isSuperAdmin(), 403);

        $review->loadMissing('performance.show');
        abort_unless(
            $review->performance?->show?->organisation_id === $request->user()->organisation_id,
            404
        );

        $validated = $request->validate([
            'moderation_status' => ['required', Rule::in(['approved', 'rejected'])],
            'moderation_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $review->update([
            'moderation_status' => $validated['moderation_status'],
            'moderation_reason' => $validated['moderation_reason'] ?? null,
        ]);

        return back()->with('status', 'Review moderation updated.');
    }
}
