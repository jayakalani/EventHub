<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminReportService;
use App\Services\CroDashboardService;
use App\Services\EventCompletionService;
use App\Services\OrganizerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected EventCompletionService $eventCompletionService,
        protected AdminReportService $adminReportService,
        protected OrganizerDashboardService $organizerDashboardService,
        protected CroDashboardService $croDashboardService,
    ) {}

    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $roleName = $user->userRole?->name_en;

        if ($roleName === UserRole::ATTENDEE) {
            $redirect = redirect()->route('attendee.dashboard');

            if (session('welcome_back')) {
                $redirect->with('welcome_back', true);
            }

            return $redirect;
        }

        return match ($roleName) {
            UserRole::ADMIN => $this->admin(),
            UserRole::ORGANIZER => $this->organizer(),
            UserRole::CRO => $this->cro(),
            default => redirect()->route('login')->with('error', 'Invalid role'),
        };
    }

    /**
     * Admin Dashboard
     */
    public function admin(): View
    {
        $dashboard = $this->adminReportService->getDashboardData();

        return view('admin.dashboard', compact('dashboard'));
    }

    /**
     * Organizer Dashboard
     */
    public function organizer(): View
    {
        $organizerId = Auth::id();

        $this->organizerDashboardService->syncLowInventoryNotifications($organizerId);

        $dashboard = $this->organizerDashboardService->getDashboardData($organizerId);

        return view('organizer.dashboard', compact('dashboard'));
    }

    /**
     * Update the organizer's monthly revenue goal.
     */
    public function updateRevenueGoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'monthly_revenue_goal' => ['required', 'numeric', 'min:1000', 'max:999999999'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->monthly_revenue_goal = $validated['monthly_revenue_goal'];
        $user->save();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Successfully set new revenue goal.');
    }

    /**
     * CRO Dashboard
     */
    public function cro(): View
    {
        $dashboard = $this->croDashboardService->getDashboardData();

        return view('cro.dashboard', compact('dashboard'));
    }

    /**
     * Public welcome page with browsable events.
     */
    public function welcome(Request $request): View
    {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $eventCategories = EventCategory::where('is_active', 1)->get();
        $selectedCategory = $request->category ?? null;

        return view('welcome', compact(
            'events',
            'eventCategories',
            'selectedCategory'
        ));
    }

    /**
     * Attendee Dashboard
     */
    public function attendee(Request $request): View
    {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $pastEvents = $this->withUserEventFlags($this->pastEventsQuery($request))->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        $selectedHost = $request->filled('host')
            ? Host::query()->where('is_active', true)->find($request->host)
            : null;

        return view('attendee.dashboard', compact(
            'events',
            'pastEvents',
            'eventCategories',
            'selectedCategory',
            'selectedHost'
        ));
    }

    private function pastEventsQuery(Request $request)
    {
        $query = Event::query()->pastForAttendees()->latest('date');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('host')) {
            $query->where('hosted_by', $request->host);
        }

        return $query->withCount('likes');
    }

    private function filteredEventsQuery(Request $request)
    {
        $query = Event::query()->activeForAttendees();

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

        return $query->withCount('likes');
    }

    private function withUserEventFlags($query)
    {
        if (! Auth::check()) {
            return $query;
        }

        return $query
            ->withExists(['likes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['saves as is_saved' => function ($saveQuery) {
                $saveQuery->where('user_id', Auth::id());
            }]);
    }
}
