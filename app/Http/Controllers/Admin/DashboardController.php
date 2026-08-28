<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organisation;
use App\Models\Review;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()->isSuperAdmin()) {
            return redirect()->route('super.organisations.index');
        }

        abort_unless($request->user()->organisation, 403, 'No organisation is assigned to this user.');
        Gate::authorize('viewDashboard', $request->user()->organisation);

        return $this->forOrganisation($request->user()->organisation);
    }

    public function forOrganisation(Organisation $organisation, bool $supportMode = false): View
    {
        Gate::authorize('viewDashboard', $organisation);

        $shows = Show::query()
            ->withCount(['performances', 'audienceAttendances'])
            ->with(['reviews' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }])
            ->whereBelongsTo($organisation)
            ->where('status', '!=', 'archived')
            ->orderBy('title')
            ->get();

        $recentReviews = Review::query()
            ->with(['performance.show', 'reviewer'])
            ->whereHas('performance.show', fn ($query) => $query->whereBelongsTo($organisation))
            ->latest('submitted_at')
            ->limit(8)
            ->get();

        $pendingReviewQuery = Review::query()
            ->with(['performance.show', 'reviewer'])
            ->where('moderation_status', 'pending')
            ->whereHas('performance.show', fn ($query) => $query->whereBelongsTo($organisation));

        $pendingReviewCount = (clone $pendingReviewQuery)->count();
        $pendingReviews = $pendingReviewQuery->latest('submitted_at')
            ->limit(8)
            ->get();

        $approvedReviews = Review::query()
            ->where('moderation_status', 'approved')
            ->whereHas('performance.show', fn ($query) => $query->whereBelongsTo($organisation))
            ->get();

        return view('admin.dashboard', [
            'organisation' => $organisation,
            'supportMode' => $supportMode,
            'shows' => $shows,
            'recentReviews' => $recentReviews,
            'pendingReviews' => $pendingReviews,
            'stats' => [
                'shows' => $shows->count(),
                'approvedReviews' => $approvedReviews->count(),
                'pendingReviews' => $pendingReviewCount,
                'averageRating' => $approvedReviews->avg('rating'),
            ],
        ]);
    }
}
