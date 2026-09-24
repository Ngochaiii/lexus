<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->status === 'published', 404);

        return view('frontend.page', [
            'page'     => $page,
            'sections' => $page->renderableSections(),
        ]);
    }
}
