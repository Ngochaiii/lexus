<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DealerController;
use App\Http\Controllers\Api\FeeCalculatorController;
use App\Http\Controllers\Api\FormController;
use App\Http\Controllers\Api\LeadController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use Illuminate\Support\Facades\Route;

/*
| Mọi response đi qua API Resource. Không bao giờ `return $product;`.
*/

Route::get('products', [ProductController::class, 'index'])->name('catalog.products.index');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('catalog.products.show');

Route::get('categories', [CategoryController::class, 'index'])->name('catalog.categories.index');
Route::get('categories/{slug}', [CategoryController::class, 'show'])->name('catalog.categories.show');

Route::get('posts', [PostController::class, 'index'])->name('catalog.posts.index');
Route::get('posts/{slug}', [PostController::class, 'show'])->name('catalog.posts.show');

Route::get('pages/{slug}', [PageController::class, 'show'])->name('catalog.pages.show');
Route::get('menus/{key}', [MenuController::class, 'show'])->name('catalog.menus.show');
Route::get('settings', [SettingController::class, 'index'])->name('catalog.settings.index');

Route::get('forms/{key}', [FormController::class, 'show'])->name('catalog.forms.show');
Route::post('leads', [LeadController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('catalog.leads.store');

if (catalog_feature('dealers')) {
    Route::get('dealers', [DealerController::class, 'index'])->name('catalog.dealers.index');
}

if (catalog_feature('fee_calc')) {
    Route::post('fee-calculator', FeeCalculatorController::class)->name('catalog.fee-calculator');
}
