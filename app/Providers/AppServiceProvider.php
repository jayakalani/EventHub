<?php

namespace App\Providers;

use App\Models\CartItem;
use App\Models\ticketBooking;
use App\Models\UserRole;
use App\Services\PostponementAlertService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            if (! Auth::check()) {
                $view->with([
                    'cartItemCount' => 0,
                    'reservedTicketCount' => 0,
                    'unreadNotificationCount' => 0,
                    'recentNotifications' => collect(),
                ]);

                return;
            }

            $user = Auth::user();
            $userId = $user->id;

            $view->with([
                'cartItemCount' => CartItem::where('user_id', $userId)->sum('quantity'),
                'reservedTicketCount' => CartItem::where('user_id', $userId)->sum('quantity'),
                'confirmedTicketCount' => ticketBooking::where('user_id', $userId)->count(),
                'unreadNotificationCount' => $user->unreadNotifications()->count(),
                'recentNotifications' => $user->notifications()->latest()->limit(5)->get(),
            ]);
        });

        View::composer('layouts.app', function ($view) {
            $alerts = collect();

            if (Auth::check()) {
                $user = Auth::user()->loadMissing('userRole');

                if ($user->userRole?->name_en === UserRole::ATTENDEE) {
                    // After login, postponement_alerts_shown is cleared so the popup can show once.
                    if (! session()->has('postponement_alerts_shown')) {
                        $alerts = app(PostponementAlertService::class)->undismissedAlertsFor($user);
                        session()->put('postponement_alerts_shown', true);
                    }
                }
            }

            $view->with('postponementLoginAlerts', $alerts);
        });
    }
}
