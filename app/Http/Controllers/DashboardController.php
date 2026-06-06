<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\UserRole;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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
        $totalEvents = Event::count();
        $totalHosts = Host::count();
        $totalAttendees = User::whereHas('userRole', function ($query) {
            $query->where('name_en', UserRole::ATTENDEE);
        })->count();
        $upcomingEvents = Event::whereDate('date', '>', now())->count();

        return view('organizer.dashboard', compact(
            'totalEvents',
            'totalHosts',
            'totalAttendees',
            'upcomingEvents'
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
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        $events = $query->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        return view('attendee.dashboard', compact(
            'events',
            'eventCategories',
            'selectedCategory'
        ));
    }

}
