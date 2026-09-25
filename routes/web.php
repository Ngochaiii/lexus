<?php

use App\Http\Controllers\Admin\MediaUploadController;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LeadController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\PostCategoryController;
use App\Http\Controllers\Frontend\PostController;
use App\Http\Controllers\Frontend\PostIndexController;
use App\Http\Controllers\Frontend\QuoteController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\VariantController;
use App\Http\Controllers\Frontend\ProductIndexController;
use App\Http\Controllers\SeoFilesController;
use App\Http\Controllers\SitemapController;
use App\Support\Url;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/*
|--------------------------------------------------------------------------
| Admin media — native PHP, không dùng Storage/Flysystem/fileinfo
|--------------------------------------------------------------------------
|
| File được lưu ngay và endpoint chỉ trả relative path. Filament đưa path đó
| vào state của form; model tiếp tục lưu chuỗi/JSON giống dữ liệu cũ.
*/
Route::post('admin/media', MediaUploadController::class)
    ->middleware(['auth', 'throttle:30,1'])
    ->name('admin.media.store');

/*
|--------------------------------------------------------------------------
| SEO
|--------------------------------------------------------------------------
*/
if (config('catalog.seo.sitemap', true)) {
    Route::get('sitemap.xml', SitemapController::class)
        // XML công khai không cần session/CSRF. Bỏ ba middleware này để
        // không phát cookie và cho Cloudflare cache an toàn.
        ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
        ->name('sitemap');
}

// robots.txt + llms.txt sinh theo APP_URL và dữ liệu thật — xem SeoFilesController.
// public/robots.txt tĩnh đã bỏ: web server sẽ trả file tĩnh trước route này.
Route::get('robots.txt', [SeoFilesController::class, 'robots'])
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('robots');
Route::get('llms.txt', [SeoFilesController::class, 'llms'])
    ->withoutMiddleware([StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class])
    ->name('llms');

/*
|--------------------------------------------------------------------------
| Frontend — trang khách xem, render bằng Blade
|--------------------------------------------------------------------------
| Tiền tố URL lấy từ config('catalog.routes') để khớp với sitemap và
| redirect. Đổi hình dạng URL chỉ sửa config.
|
| Route của loại nội dung nào cũng gắn với feature của nó: tắt `posts` trong
| config là /tin-tuc trả 404, đúng như /dealers bên API.
*/
Route::get('/', HomeController::class)->name('home');

$productPrefix = trim(Url::prefix('product'), '/');

Route::get($productPrefix, ProductIndexController::class)->name('products.index');
Route::get($productPrefix.'/{product:slug}', ProductController::class)->name('products.show');
// Trang riêng từng phiên bản (/san-pham/rx/rx-350h-premium) — scopeBindings:
// phiên bản phải thuộc đúng dòng xe trong URL, sai thì 404.
Route::get($productPrefix.'/{product:slug}/{variant:slug}', VariantController::class)
    ->scopeBindings()->name('variants.show');

Route::get(trim(Url::prefix('category'), '/').'/{category:slug}', CategoryController::class)
    ->name('categories.show');

if (catalog_feature('posts')) {
    $postPrefix = trim(Url::prefix('post'), '/');

    Route::get($postPrefix, PostIndexController::class)->name('posts.index');
    Route::get($postPrefix.'/{post:slug}', PostController::class)->name('posts.show');

    Route::get(trim(Url::prefix('post_category'), '/').'/{postCategory:slug}', PostCategoryController::class)
        ->name('post-categories.show');
}

/*
|--------------------------------------------------------------------------
| Trang cố định — không theo slug
|--------------------------------------------------------------------------
| Trang đăng ký lái thử không gắn với bản ghi nào. Tiền tố URL lấy từ
| config('catalog.routes'), bật/tắt ở config('catalog.frontend').
|
| Các trang phụ kiện / đại lý / trạm sạc của bản VinFast đã gỡ khỏi bản này
| — xem resources/views/README.md, mục phạm vi.
|
| Phải khai TRƯỚC route trang tĩnh /{page:slug} ở cuối file.
*/
if (catalog_feature('forms') && filled(config('catalog.frontend.booking.forms'))) {
    Route::get(trim(Url::prefix('booking'), '/'), BookingController::class)->name('booking');

    // Trang nhận báo giá — đích của mọi nút "Báo giá" khi không có JS.
    Route::get(trim(Url::prefix('quote'), '/'), QuoteController::class)->name('quote');
}


/*
|--------------------------------------------------------------------------
| Lead — POST thường, được nâng cấp thành fetch khi có JavaScript
|--------------------------------------------------------------------------
| Có JS: endpoint trả JSON để trang không reload. Tắt JS: redirect về đúng
| trang cũ kèm câu cảm ơn. Cùng action nên validation/chống trùng y hệt.
*/
if (catalog_feature('forms')) {
    Route::post('gui-form/{form:key}', LeadController::class)
        ->middleware('throttle:10,1')
        ->name('leads.store');
}

// Trang tĩnh nằm ở gốc (/gioi-thieu) nên đặt CUỐI cùng để không nuốt route khác.
if (catalog_feature('pages')) {
    Route::get('/{page:slug}', PageController::class)->name('pages.show');
}
