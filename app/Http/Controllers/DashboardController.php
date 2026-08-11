<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminReportService;
use App\Services\AttendeeCalendarService;
use App\Services\CroDashboardService;
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
            $filters['focus_event'],
            $filters['override_flags'],
            $filters['query'],
        );

        return view('organizer.dashboard', compact('dashboard'));
    }

    /**
     * Lightweight JSON pulse for live Today summary + recent sales.
     */
    public function organizerLive(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->organizerDashboardService->getLivePulse((int) Auth::id())
        );
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
        if (! $request->filled('focus_event')) {
            $request->merge(['focus_event' => null]);
        }

        $validated = $request->validate([
            'revenue_goal' => ['required', 'numeric', 'min:1000', 'max:999999999'],
            'goal_event' => ['nullable', 'integer', 'exists:events,id'],
            'focus_event' => ['nullable', 'integer', 'exists:events,id'],
            'kpi_event' => ['nullable', 'string', 'max:32'],
            'chart_event' => ['nullable', 'string', 'max:32'],
            'engagement_event' => ['nullable', 'string', 'max:32'],
        ]);

        $redirectParams = $this->organizerDashboardRedirectParams($request);

        if (! empty($validated['goal_event'])) {
            $event = Event::query()
                ->createdByOrganizer(Auth::id())
                ->findOrFail($validated['goal_event']);

            $event->revenue_goal = $validated['revenue_goal'];
            $event->save();

            // Keep the goal section pinned to this event after save.
            $redirectParams['goal_event'] = (string) $event->id;

            return redirect()
                ->route('organizer.dashboard', $redirectParams)
                ->with('success', 'Successfully set revenue goal for '.$event->name.'.');
        }

        /** @var User $user */
        $user = Auth::user();
        $user->monthly_revenue_goal = $validated['revenue_goal'];
        $user->save();

        // Monthly goal — clear any goal-section override so it follows focus.
        unset($redirectParams['goal_event']);

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
        $dashboard = $this->croDashboardService->getDashboardData($filters, (int) Auth::id());

        return view('cro.dashboard', compact('dashboard'));
    }

    /**
     * Export the CRO dashboard as PDF.
     */
    public function exportCroPdf(Request $request)
    {
        abort_unless(Auth::user()?->userRole?->name_en === UserRole::CRO, 403);

        $filters = $this->validatedCroDashboardFilters($request);
        $payload = $this->croDashboardExportBuilder->build($filters, (int) Auth::id());
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
        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $pastEvents = $this->withUserEventFlags($this->pastEventsQuery($request))->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        $selectedArtist = $request->filled('artist')
            ? Artist::query()->where('is_active', true)->find($request->artist)
            : null;

        $upcomingThisWeek = $calendarService->getThisWeekBookedEvents((int) Auth::id());
        $pendingRatingPrompts = $eventNotificationService->getPendingRatingPrompts((int) Auth::id());

        return view('attendee.dashboard', compact(
            'events',
            'pastEvents',
            'eventCategories',
            'selectedCategory',
            'selectedArtist',
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
     *     focus_event: int|null,
     *     kpi_event: int|null,
     *     goal_event: int|null,
     *     chart_event: int|null,
     *     engagement_event: int|null,
     *     override_flags: array{kpi: bool, goal: bool, chart: bool, engagement: bool},
     *     query: array<string, int|string>
     * }
     */
    private function validatedOrganizerDashboardFilters(Request $request): array
    {
        $organizerId = (int) Auth::id();

        $eventRule = [
            'nullable',
            'integer',
            Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', $organizerId)),
        ];

        $sectionRule = [
            'nullable',
            'string',
            'max:32',
            function (string $attribute, mixed $value, \Closure $fail) use ($organizerId) {
                if ($value === null || $value === '' || $value === 'focus' || $value === 'all') {
                    return;
                }

                if (! ctype_digit((string) $value)) {
                    $fail('The selected event is invalid.');

                    return;
                }

                $exists = Event::query()
                    ->createdByOrganizer($organizerId)
                    ->whereKey((int) $value)
                    ->exists();

                if (! $exists) {
                    $fail('The selected event is invalid.');
                }
            },
        ];

        if (! $request->filled('focus_event')) {
            $request->merge(['focus_event' => null]);
        }

        $validated = $request->validate([
            'focus_event' => $eventRule,
            'kpi_event' => $sectionRule,
            'goal_event' => $sectionRule,
            'chart_event' => $sectionRule,
            'engagement_event' => $sectionRule,
        ]);

        $focusEvent = isset($validated['focus_event']) ? (int) $validated['focus_event'] : null;
        $hasFocusParam = $request->query->has('focus_event') || $request->request->has('focus_event');

        $sections = [
            'kpi' => 'kpi_event',
            'goal' => 'goal_event',
            'chart' => 'chart_event',
            'engagement' => 'engagement_event',
        ];

        $overrideFlags = [
            'kpi' => false,
            'goal' => false,
            'chart' => false,
            'engagement' => false,
        ];

        $effective = [
            'kpi_event' => $focusEvent,
            'goal_event' => $focusEvent,
            'chart_event' => $focusEvent,
            'engagement_event' => $focusEvent,
        ];

        $query = [];
        if ($focusEvent !== null) {
            $query['focus_event'] = $focusEvent;
        } elseif ($hasFocusParam) {
            // Explicit "All Events" focus — keep param out of query (clean URL).
        }

        // Legacy URLs used integer-only section params without focus_event.
        $legacyMode = ! $hasFocusParam;
        $legacyIds = [];

        foreach ($sections as $flag => $param) {
            if (! $request->query->has($param) && ! $request->request->has($param)) {
                continue;
            }

            $raw = $validated[$param] ?? null;

            if ($raw === null || $raw === '' || $raw === 'focus') {
                continue;
            }

            if ($raw === 'all') {
                $overrideFlags[$flag] = true;
                $effective[$param] = null;
                $query[$param] = 'all';

                continue;
            }

            $eventId = (int) $raw;

            if ($legacyMode && ctype_digit((string) $raw)) {
                $legacyIds[$param] = $eventId;
                $overrideFlags[$flag] = true;
                $effective[$param] = $eventId;
                $query[$param] = $eventId;

                continue;
            }

            $overrideFlags[$flag] = true;
            $effective[$param] = $eventId;
            $query[$param] = $eventId;
        }

        if ($legacyMode && $legacyIds !== []) {
            $unique = array_values(array_unique(array_values($legacyIds)));

            if (count($unique) === 1) {
                $focusEvent = $unique[0];
                $query = ['focus_event' => $focusEvent];
                $overrideFlags = [
                    'kpi' => false,
                    'goal' => false,
                    'chart' => false,
                    'engagement' => false,
                ];
                $effective = [
                    'kpi_event' => $focusEvent,
                    'goal_event' => $focusEvent,
                    'chart_event' => $focusEvent,
                    'engagement_event' => $focusEvent,
                ];
            } else {
                $focusEvent = null;
                // Keep independent section values from the loop above; drop focus.
                $query = collect($legacyIds)->all();
            }
        }

        return [
            'focus_event' => $focusEvent,
            'kpi_event' => $effective['kpi_event'],
            'goal_event' => $effective['goal_event'],
            'chart_event' => $effective['chart_event'],
            'engagement_event' => $effective['engagement_event'],
            'override_flags' => $overrideFlags,
            'query' => $query,
        ];
    }

    /**
     * Preserve focus + section overrides across revenue-goal redirects.
     *
     * @return array<string, int|string>
     */
    private function organizerDashboardRedirectParams(Request $request): array
    {
        return $this->validatedOrganizerDashboardFilters($request)['query'];
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

        if ($request->filled('artist')) {
            $query->whereHas('artists', function ($artistQuery) use ($request) {
                $artistQuery->where('artists.id', $request->artist);
            });
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

        if ($request->filled('artist')) {
            $query->whereHas('artists', function ($artistQuery) use ($request) {
                $artistQuery->where('artists.id', $request->artist);
            });
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
