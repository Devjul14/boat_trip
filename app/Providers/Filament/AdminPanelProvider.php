<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Log;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\NavigationGroup;
use Rupadana\ApiService\ApiServicePlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationBuilder;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use App\Filament\Resources\TripPassengersResource;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Boat Trip')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->resources([
                // Resource lainnya
                TripPassengersResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                ApiServicePlugin::make()
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                // Debug: Log user info dan roles
                $isAdmin = false;
                $isManager = false;
                $isSuperAdmin = false;
                $isBoatman = false;
            
                if (auth()->check()) {
                    $user = auth()->user();
                    $rolesInDB = $user->roles()->pluck('name')->toArray();
                    $roleInColumn = $user->role;
                    
                    Log::info('User roles info', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'role_column' => $roleInColumn,
                        'spatie_roles' => $rolesInDB,
                    ]);
                    
                    // NOTE: Ini untuk menampilkan menu berdasarkan kolom 'role' juga
                    $isAdmin = $user->hasRole('admin') || $roleInColumn === 'admin';
                    $isManager = $user->hasRole('manager') || $roleInColumn === 'manager';
                    $isSuperAdmin = $user->hasRole('Super Admin') || $roleInColumn === 'Super Admin';
                    $isBoatman = $user->hasRole('boatman') || $roleInColumn === 'boatman';
                }
                
                // Dashboard untuk semua pengguna
                $builder->item(
                    NavigationItem::make('Dashboard')
                        ->icon('heroicon-o-home')
                        ->url(fn (): string => Pages\Dashboard::getUrl())
                        ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.pages.dashboard'))
                );
                
                // Grup Operations untuk semua pengguna
                $operationsItems = [];
                
                // Trips untuk semua role
                $operationsItems[] = NavigationItem::make('Trips')
                    ->icon('heroicon-o-paper-airplane')
                    ->url(fn (): string => route('filament.admin.resources.trips.index'))
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.trips.*'));
                
                
                    $operationsItems[] = NavigationItem::make('Tikets')
                        ->icon('heroicon-o-ticket')
                        ->url(fn (): string => route('filament.admin.resources.trip-passengers.index'))
                        ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.trip-passengers.*'));
                
                
                // Hotels untuk Admin, Manager, Super Admin
                if ($isAdmin || $isManager || $isSuperAdmin) {
                    $operationsItems[] = NavigationItem::make('Hotels')
                        ->icon('heroicon-o-building-office')
                        ->url(fn (): string => route('filament.admin.resources.hotels.index'))
                        ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.hotels.*'));
                }
                
                $builder->group('Operations', $operationsItems);
                
                // Grup Finance untuk Admin, Manager, Super Admin
                if ($isAdmin || $isManager || $isSuperAdmin) {
                    $financeItems = [];
                    
                    // invoices untuk Admin, Super Admin
                    if ($isAdmin || $isSuperAdmin) {
                        $financeItems[] = NavigationItem::make('Invoices')
                            ->icon('heroicon-o-currency-dollar')
                            ->url(fn (): string => route('filament.admin.resources.invoices.index'))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.invoices.*'));
                    }
                    
                    if (!empty($financeItems)) {
                        $builder->group('Finance', $financeItems);
                    }
                }
                
                // Grup Administration untuk Admin, Super Admin
                if ($isAdmin || $isSuperAdmin) {
                    $adminItems = [
                        NavigationItem::make('Users')
                            ->icon('heroicon-o-users')
                            ->url(fn (): string => route('filament.admin.resources.users.index'))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.users.*')),
                        
                        NavigationItem::make('Trip Types')
                            ->icon('heroicon-o-tag')
                            ->url(fn (): string => route('filament.admin.resources.trip-types.index'))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.trip-types.*')),
                        
                        NavigationItem::make('Boats')
                            ->icon('heroicon-o-truck')
                            ->url(fn (): string => route('filament.admin.resources.boats.index'))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.boats.*')),
                    ];
                    
                    $builder->group('Administration', $adminItems);
                }
                
                // Grup Settings hanya untuk Super Admin
                if ($isSuperAdmin) {
                    $builder->group('Settings', [
                        NavigationItem::make('Roles')
                            ->icon('heroicon-o-shield-check')
                            ->url(fn (): string => route('filament.admin.resources.shield.roles.index'))
                            ->isActiveWhen(fn (): bool => request()->routeIs('filament.admin.resources.shield.roles.*')),
                    ]);
                }
                
                return $builder;
            });
    }
}