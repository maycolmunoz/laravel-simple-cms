<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;

class PageController extends Controller
{
    /**
     * @param string $slug
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function show(string $slug)
    {
        $page = Page::published()->with(['children' => fn ($q) => $q->published()])->where('slug', $slug)->firstOrFail();
        return view('frontend.pages.show', compact('page'));
    }
}
