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
            UserRole::ORGANIZER => $this->organizer(request()),
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
    public function organizer(Request $request): View
    {
        $organizerId = Auth::id();

        $this->organizerDashboardService->syncLowInventoryNotifications($organizerId);

        $kpiEventId = $request->filled('kpi_event') ? $request->integer('kpi_event') : null;
        $goalEventId = $request->filled('goal_event') ? $request->integer('goal_event') : null;
        $chartEventId = $request->filled('chart_event') ? $request->integer('chart_event') : null;
        $engagementEventId = $request->filled('engagement_event') ? $request->integer('engagement_event') : null;
        $dashboard = $this->organizerDashboardService->getDashboardData(
            $organizerId,
            $kpiEventId,
            $goalEventId,
            $chartEventId,
            $engagementEventId,
        );

        return view('organizer.dashboard', compact('dashboard'));
    }

    /**
     * Update the organizer monthly revenue goal or a per-event revenue goal.
     */
    public function updateRevenueGoal(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'revenue_goal' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'goal_event' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        $redirectParams = array_filter([
            'kpi_event' => $request->filled('kpi_event') ? $request->integer('kpi_event') : null,
            'chart_event' => $request->filled('chart_event') ? $request->integer('chart_event') : null,
            'engagement_event' => $request->filled('engagement_event') ? $request->integer('engagement_event') : null,
        ]);

        if (! empty($validated['goal_event'])) {
            $event = Event::query()
                ->createdByOrganizer(Auth::id())
                ->findOrFail($validated['goal_event']);

            $event->revenue_goal = $validated['revenue_goal'];
            $event->save();

            $redirectParams['goal_event'] = $event->id;

            return redirect()
                ->route('organizer.dashboard', $redirectParams)
                ->with('success', 'Successfully set revenue goal for '.$event->name.'.');
        }

        /** @var User $user */
        $user = Auth::user();
        $user->monthly_revenue_goal = $validated['revenue_goal'];
        $user->save();

        return redirect()
            ->route('organizer.dashboard', $redirectParams)
            ->with('success', 'Successfully set monthly revenue goal.');
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
