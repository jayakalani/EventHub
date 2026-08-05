<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminReportService;
use App\Services\AttendeeCalendarService;
use App\Services\CroDashboardService;
use App\Services\EventCompletionService;
use App\Services\EventNotificationService;
use App\Services\Exports\AdminDashboardExportBuilder;
use App\Services\Exports\CroDashboardExportBuilder;
use App\Services\Exports\OrganizerDashboardExportBuilder;
use App\Services\OrganizerDashboardService;
use App\Services\ReportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected EventCompletionService $eventCompletionService,
        protected AdminReportService $adminReportService,
        protected OrganizerDashboardService $organizerDashboardService,
        protected CroDashboardService $croDashboardService,
        protected ReportExportService $exportService,
        protected AdminDashboardExportBuilder $adminDashboardExportBuilder,
        protected OrganizerDashboardExportBuilder $organizerDashboardExportBuilder,
        protected CroDashboardExportBuilder $croDashboardExportBuilder,
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
            UserRole::ADMIN => $this->admin(request()),
            UserRole::ORGANIZER => $this->organizer(request()),
            UserRole::CRO => $this->cro(request()),
            default => redirect()->route('login')->with('error', 'Invalid role'),
        };
    }

    /**
     * Admin Dashboard
     */
    public function admin(Request $request): View
    {
        $filters = $this->validatedAdminDashboardFilters($request);
        $dashboard = $this->adminReportService->getDashboardData(
            $filters['organizer'],
            $filters['event'],
            $filters['payment_organizer'],
            $filters['payment_event'],
            $filters['support_cro'],
            $filters['support_event'],
        );

        return view('admin.dashboard', compact('dashboard'));
    }

    /**
     * Export the admin dashboard as PDF (respects current dropdown filters).
     */
    public function exportAdminPdf(Request $request)
    {
        abort_unless(Auth::user()?->userRole?->name_en === UserRole::ADMIN, 403);

        $filters = $this->validatedAdminDashboardFilters($request);
        $payload = $this->adminDashboardExportBuilder->build($filters);
        $payload['charts'] = $this->validatedDashboardChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            sprintf('admin-dashboard-%s.pdf', now()->format('Y-m-d-His')),
        );
    }

    /**
     * Organizer Dashboard
     */
    public function organizer(Request $request): View
    {
        $organizerId = Auth::id();

        $this->organizerDashboardService->syncLowInventoryNotifications($organizerId);

        $filters = $this->validatedOrganizerDashboardFilters($request);
        $dashboard = $this->organizerDashboardService->getDashboardData(
            $organizerId,
            $filters['kpi_event'],
            $filters['goal_event'],
            $filters['chart_event'],
            $filters['engagement_event'],
        );

        return view('organizer.dashboard', compact('dashboard'));
    }

    /**
     * Export the organizer dashboard as PDF (respects current event filters).
     */
    public function exportOrganizerPdf(Request $request)
    {
        abort_unless(Auth::user()?->userRole?->name_en === UserRole::ORGANIZER, 403);

        $filters = $this->validatedOrganizerDashboardFilters($request);
        $payload = $this->organizerDashboardExportBuilder->build((int) Auth::id(), $filters);
        $payload['charts'] = $this->validatedDashboardChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            sprintf('organizer-dashboard-%s.pdf', now()->format('Y-m-d-His')),
            'organizer.exports.dashboard-pdf',
        );
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
    public function cro(Request $request): View
    {
        $filters = $this->validatedCroDashboardFilters($request);
        $dashboard = $this->croDashboardService->getDashboardData($filters);

        return view('cro.dashboard', compact('dashboard'));
    }

    /**
     * Export the CRO dashboard as PDF.
     */
    public function exportCroPdf(Request $request)
    {
        abort_unless(Auth::user()?->userRole?->name_en === UserRole::CRO, 403);

        $filters = $this->validatedCroDashboardFilters($request);
        $payload = $this->croDashboardExportBuilder->build($filters);
        $payload['charts'] = $this->validatedDashboardChartImages($request);

        return $this->exportService->downloadPdf(
            $payload,
            sprintf('cro-dashboard-%s.pdf', now()->format('Y-m-d-His')),
        );
    }

    /**
     * Public welcome page with browsable events.
     */
    public function welcome(Request $request): View
    {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $carouselEvents = $this->withUserEventFlags(
            Event::query()->activeForAttendees()->withCount('likes')
        )->get();
        $eventCategories = EventCategory::where('is_active', 1)->get();
        $selectedCategory = $request->category ?? null;

        return view('welcome', compact(
            'events',
            'carouselEvents',
            'eventCategories',
            'selectedCategory'
        ));
    }

    /**
     * Attendee Dashboard
     */
    public function attendee(
        Request $request,
        AttendeeCalendarService $calendarService,
        EventNotificationService $eventNotificationService,
    ): View {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $pastEvents = $this->withUserEventFlags($this->pastEventsQuery($request))->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        $selectedHost = $request->filled('host')
            ? Host::query()->where('is_active', true)->find($request->host)
            : null;

        $upcomingThisWeek = $calendarService->getThisWeekBookedEvents((int) Auth::id());
        $pendingRatingPrompts = $eventNotificationService->getPendingRatingPrompts((int) Auth::id());

        return view('attendee.dashboard', compact(
            'events',
            'pastEvents',
            'eventCategories',
            'selectedCategory',
            'selectedHost',
            'upcomingThisWeek',
            'pendingRatingPrompts',
        ));
    }

    /**
     * @return list<array{title: string, image: string}>
     */
    private function validatedDashboardChartImages(Request $request): array
    {
        $validated = $request->validate([
            'charts' => ['nullable', 'array', 'max:12'],
            'charts.*.title' => ['required', 'string', 'max:120'],
            'charts.*.image' => ['required', 'string', 'max:5000000'],
        ]);

        return collect($validated['charts'] ?? [])
            ->filter(function (array $chart) {
                $image = $chart['image'] ?? '';

                return is_string($image)
                    && preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $image) === 1;
            })
            ->map(fn (array $chart) => [
                'title' => (string) $chart['title'],
                'image' => (string) $chart['image'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     organizer: int|null,
     *     event: int|null,
     *     payment_organizer: int|null,
     *     payment_event: int|null,
     *     support_cro: int|null,
     *     support_event: int|null
     * }
     */
    private function validatedAdminDashboardFilters(Request $request): array
    {
        $request->merge([
            'organizer' => $request->filled('organizer') ? $request->input('organizer') : null,
            'event' => $request->filled('event') ? $request->input('event') : null,
            'payment_organizer' => $request->filled('payment_organizer') ? $request->input('payment_organizer') : null,
            'payment_event' => $request->filled('payment_event') ? $request->input('payment_event') : null,
            'support_cro' => $request->filled('support_cro') ? $request->input('support_cro') : null,
            'support_event' => $request->filled('support_event') ? $request->input('support_event') : null,
        ]);

        $validated = $request->validate([
            'organizer' => ['nullable', 'integer', 'exists:users,id'],
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'payment_organizer' => ['nullable', 'integer', 'exists:users,id'],
            'payment_event' => ['nullable', 'integer', 'exists:events,id'],
            'support_cro' => ['nullable', 'integer', 'exists:users,id'],
            'support_event' => ['nullable', 'integer', 'exists:events,id'],
        ]);

        return [
            'organizer' => isset($validated['organizer']) ? (int) $validated['organizer'] : null,
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'payment_organizer' => isset($validated['payment_organizer']) ? (int) $validated['payment_organizer'] : null,
            'payment_event' => isset($validated['payment_event']) ? (int) $validated['payment_event'] : null,
            'support_cro' => isset($validated['support_cro']) ? (int) $validated['support_cro'] : null,
            'support_event' => isset($validated['support_event']) ? (int) $validated['support_event'] : null,
        ];
    }

    /**
     * @return array{
     *     kpi_event: int|null,
     *     goal_event: int|null,
     *     chart_event: int|null,
     *     engagement_event: int|null
     * }
     */
    private function validatedOrganizerDashboardFilters(Request $request): array
    {
        $organizerId = (int) Auth::id();

        $request->merge([
            'kpi_event' => $request->filled('kpi_event') ? $request->input('kpi_event') : null,
            'goal_event' => $request->filled('goal_event') ? $request->input('goal_event') : null,
            'chart_event' => $request->filled('chart_event') ? $request->input('chart_event') : null,
            'engagement_event' => $request->filled('engagement_event') ? $request->input('engagement_event') : null,
        ]);

        $eventRule = [
            'nullable',
            'integer',
            Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', $organizerId)),
        ];

        $validated = $request->validate([
            'kpi_event' => $eventRule,
            'goal_event' => $eventRule,
            'chart_event' => $eventRule,
            'engagement_event' => $eventRule,
        ]);

        return [
            'kpi_event' => isset($validated['kpi_event']) ? (int) $validated['kpi_event'] : null,
            'goal_event' => isset($validated['goal_event']) ? (int) $validated['goal_event'] : null,
            'chart_event' => isset($validated['chart_event']) ? (int) $validated['chart_event'] : null,
            'engagement_event' => isset($validated['engagement_event']) ? (int) $validated['engagement_event'] : null,
        ];
    }

    /**
     * @return array{event: int|null, from: string|null, to: string|null}
     */
    private function validatedCroDashboardFilters(Request $request): array
    {
        $request->merge([
            'event' => $request->filled('event') ? $request->input('event') : null,
            'from' => $request->filled('from') ? $request->input('from') : null,
            'to' => $request->filled('to') ? $request->input('to') : null,
        ]);

        $validated = $request->validate([
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return [
            'event' => isset($validated['event']) ? (int) $validated['event'] : null,
            'from' => $validated['from'] ?? null,
            'to' => $validated['to'] ?? null,
        ];
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
