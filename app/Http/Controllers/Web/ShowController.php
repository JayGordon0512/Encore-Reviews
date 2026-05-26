<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Show;
use Illuminate\Http\Request;

class ShowController extends Controller
{
    public function index()
    {
        $shows = Show::query()
            ->where('status', '!=', 'archived')
            ->orderBy('title')
            ->get();

        return view('public.shows', [
            'shows' => $shows,
        ]);
    }

    public function show(Request $request, Show $show)
    {
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
