<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\Orders\Pages\ListOrdersDelivered;
use App\Filament\Admin\Resources\Orders\Pages\ListOrdersReadyToShip;
use App\Filament\Admin\Resources\Orders\Pages\ListOrdersShipped;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use App\Http\Middleware\AdminOtpMiddleware;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
// use App\Filament\Pages\Auth\EditProfile;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->login()
            ->profile(isSimple: false, page: EditProfile::class)
            ->path('dashboard')
            ->brandName('لوحة التحكم')
            ->brandLogo(asset('assets/images/logo.svg'))
            ->brandLogoHeight('60px')
            ->sidebarWidth('280px')
            // ->domain(env('ADMIN_URL'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' =>
                Color::Blue,
            ])
            ->userMenuItems([
                'profile' => fn (Action $action) => $action->label('Edit profile'),

            ])
            ->navigationGroups([
                NavigationGroup::make('المبيعات والمدفوعات')
                    ->icon('heroicon-o-shopping-cart')
                    ->collapsible(),

                NavigationGroup::make('إدارة المتجر')
                    ->icon('heroicon-o-shopping-bag')
                    ->collapsible(),

                NavigationGroup::make('العملاء')
                    ->icon('heroicon-o-users')
                    ->collapsible(),

                NavigationGroup::make('المحتوى التسويقي')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(),

                NavigationGroup::make('الدعم الفني')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->collapsible(),

                NavigationGroup::make('الإعدادات والنظام')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsible(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                // FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\SetAdminLocale::class,
            ])
            ->authGuard('admin')
            ->authMiddleware([
                Authenticate::class,
                AdminOtpMiddleware::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}
