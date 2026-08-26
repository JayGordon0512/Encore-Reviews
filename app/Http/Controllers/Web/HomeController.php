<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Show;

class HomeController extends Controller
{
    public function index()
    {
        $shows = Show::query()
            ->with(['reviews' => function ($query): void {
                $query->where('moderation_status', 'approved');
            }])
            ->where('status', '!=', 'archived')
            ->orderBy('title')
            ->get();

        return view('public.home', [
            'shows' => $shows,
        ]);
    }

    public function about()
    {
        return view('public.about');
    }

    public function organisers()
    {
        return view('public.organisers');
    }
}
