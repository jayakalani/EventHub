<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $roleName = $user->userRole?->name_en;

        return match ($roleName) {
            UserRole::ADMIN => $this->admin(),
            UserRole::ORGANIZER => $this->organizer(),
            UserRole::CRO => $this->cro(),
            UserRole::ATTENDEE => redirect()->route('attendee.dashboard'),
            default => redirect()->route('login')->with('error', 'Invalid role'),
        };
    }

    /**
     * Admin Dashboard
     */
    public function admin(): View
    {
        $totalUsers = User::count();
        $pendingVerifications = User::whereNull('email_verified_at')->count();
        $lockedAccounts = User::where('is_locked', 1)->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'pendingVerifications',
            'lockedAccounts'
        ));
    }

    /**
     * Organizer Dashboard
     */
    public function organizer(): View
    {
        $organizerId = Auth::id();

        $totalEvents = Event::where('created_by', $organizerId)->count();
        $totalAttendees = User::whereHas('userRole', function ($query) {
            $query->where('name_en', UserRole::ATTENDEE);
        })->count();
        $upcomingEvents = Event::where('created_by', $organizerId)
            ->whereDate('date', '>', now())
            ->count();

        $events = Event::where('created_by', $organizerId)
            ->latest()
            ->limit(5)
            ->get();

        return view('organizer.dashboard', compact(
            'totalEvents',
            'totalAttendees',
            'upcomingEvents',
            'events'
        ));
    }

    /**
     * CRO Dashboard
     */
    public function cro(): View
    {
        return view('cro.dashboard');
    }

    /**
     * Attendee Dashboard
     */
    public function attendee(Request $request): View
    {
        $query = Event::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('host')) {
            $query->where('hosted_by', $request->host);
        }

        $events = $query
            ->withCount('likes')
            ->withExists(['likes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['saves as is_saved' => function ($saveQuery) {
                $saveQuery->where('user_id', Auth::id());
            }])
            ->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        $selectedHost = $request->filled('host')
            ? Host::query()->where('is_active', true)->find($request->host)
            : null;

        return view('attendee.dashboard', compact(
            'events',
            'eventCategories',
            'selectedCategory',
            'selectedHost'
        ));
    }
}
