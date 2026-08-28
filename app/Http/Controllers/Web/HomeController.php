<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Show;

class HomeController extends Controller
{
    public function index()
    {
        $shows = Show::query()
            ->withCount(['reviews as approved_reviews_count' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }])
            ->withAvg(['reviews as approved_reviews_avg_rating' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }], 'rating')
            ->where('status', '!=', 'archived')
            ->orderBy('title')
            ->get();

        return view('public.home', [
            'shows' => $shows,
        ]);
    }

    public function organisers()
    {
        return view('public.organisers');
    }
}
