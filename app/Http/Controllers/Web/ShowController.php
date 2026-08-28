<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShowController extends Controller
{
    public function index(Request $request)
    {
        $search = Str::of((string) $request->query('q'))->trim()->limit(100, '')->toString();
        $requestedStatus = (string) $request->query('status');
        $status = in_array($requestedStatus, ['upcoming', 'now_playing'], true)
            ? $requestedStatus
            : null;

        $shows = Show::query()
            ->withCount(['reviews as approved_reviews_count' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }])
            ->withAvg(['reviews as approved_reviews_avg_rating' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }], 'rating')
            ->where('status', '!=', 'archived')
            ->when($search !== '', function ($query) use ($search): void {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($term): void {
                    $query
                        ->whereRaw('LOWER(title) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(summary) LIKE ?', [$term])
                        ->orWhereRaw('LOWER(genre) LIKE ?', [$term]);
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('title')
            ->paginate(12)
            ->withQueryString();

        return view('public.shows', [
            'shows' => $shows,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function show(Request $request, Show $show)
    {
        $show->load(['performances' => fn ($query) => $query->with('venue')->orderBy('starts_at')]);
        $reviews = $show->reviews()
            ->with('reviewer')
            ->where('moderation_status', 'approved')
            ->orderByDesc('submitted_at')
            ->get();

        $averageRating = $reviews->avg('rating');
        $recommendCount = $reviews->where('would_recommend', true)->count();
        $reviewCount = $reviews->count();

        return view('public.show', [
            'show' => $show,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
            'recommendCount' => $recommendCount,
            'reviewCount' => $reviewCount,
        ]);
    }
}
