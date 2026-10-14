<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\PriceList;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __invoke(Page $page): View
    {
        abort_unless($page->status === 'published', 404);

        // Bảng giá: mô tả (meta + JSON-LD) theo giá phiên bản, không theo câu ghi tay.
        if ($page->slug === 'bang-gia' && ($description = PriceList::description())) {
            $page->seo = array_merge($page->seo ?? [], ['description' => $description]);
        }

        return view('frontend.page', [
            'page'     => $page,
            'sections' => $page->renderableSections(),
        ]);
    }
}
