<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Show;

class HomeController extends Controller
{
    public function index()
    {
        $shows = Show::query()
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
}
