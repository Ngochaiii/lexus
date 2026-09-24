<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Banners\BannerResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\PostCategories\PostCategoryResource;
use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Redirects\RedirectResource;
use App\Support\Catalog;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            // Đăng ký tường minh + gate theo features: tắt khối nào trong
            // config thì admin không lộ khối đó ra.
            ->resources(array_values(array_filter([
                ProductResource::class,
                CategoryResource::class,

                Catalog::feature('banners') ? BannerResource::class : null,

                Catalog::feature('posts') ? PostResource::class : null,
                Catalog::feature('posts') ? PostCategoryResource::class : null,
                Catalog::feature('pages') ? PageResource::class : null,

                MenuResource::class,
                RedirectResource::class,

                // Form cố định trong code (LexusSiteSeeder), không cho sửa ở
                // admin — chủ website chỉ cần xem thông tin người gửi.
                Catalog::feature('forms') ? LeadResource::class : null,
            ])))
            ->pages([
                Dashboard::class,
                ManageSettings::class,
            ])
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
