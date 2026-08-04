<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\AuditLog;
use App\Models\CartItem;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\Inquiry;
use App\Models\Payment;
use App\Models\RefundRequest;
use App\Models\ticketBooking;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AdminReportService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardData(
        ?int $organizerId = null,
        ?int $eventId = null,
        ?int $paymentOrganizerId = null,
        ?int $paymentEventId = null,
        ?int $supportCroId = null,
        ?int $supportEventId = null,
    ): array {
        $users = $this->getUserReports();
        $payments = $this->getPaymentReports();
        $admin = $this->getAdminReports();
        $chartLabels = $this->lastSixMonthLabels();
        $shortChartLabels = $this->lastSixMonthShortLabels();

        $eventsByStatus = collect($admin['eventsByStatus'])->keyBy('label');
        $statusCount = fn (string $label): int => (int) ($eventsByStatus->get(ucfirst($label))['count'] ?? 0);

        $currentMonthUsers = (int) ($users['registrationTrend'][count($users['registrationTrend']) - 1] ?? 0);
        $previousMonthUsers = (int) ($users['registrationTrend'][count($users['registrationTrend']) - 2] ?? 0);
        $userGrowthPercent = $previousMonthUsers > 0
            ? round((($currentMonthUsers - $previousMonthUsers) / $previousMonthUsers) * 100, 1)
            : ($currentMonthUsers > 0 ? 100.0 : 0.0);

        $ticketSalesByCategory = $this->ticketSalesByCategory();
        $weeklyTicketSales = $this->weeklyTicketSales();
        $monthlyRevenue = collect($chartLabels)
            ->zip($payments['revenueTrend'])
            ->map(fn ($pair) => ['month' => $pair[0], 'amount' => $pair[1]])
            ->all();

        $scopeFilter = $this->resolveScopeFilter($organizerId, $eventId);
        $paymentScopeFilter = $this->resolveScopeFilter($paymentOrganizerId, $paymentEventId);
        $supportScopeFilter = $this->resolveCroScopeFilter($supportCroId, $supportEventId);
        $tickets = $this->getTicketReports();
        $kpis = $this->buildScopedKpis($scopeFilter, [
            'usersTotal' => $users['totalUsers'],
            'userGrowthPercent' => $userGrowthPercent,
            'eventsTotal' => $admin['totalEvents'],
            'eventsOngoing' => $statusCount('ongoing'),
            'eventsCompleted' => $statusCount('completed'),
            'revenueGross' => $payments['totalRevenue'],
            'revenueNet' => $payments['netRevenue'],
            'ticketsSold' => $tickets['sold'],
            'ticketsReserved' => $tickets['reserved'],
        ]);
        $platformAnalytics = $this->scopedPlatformAnalytics($scopeFilter, [
            'active' => $statusCount('ongoing'),
            'completed' => $statusCount('completed'),
            'cancelled' => $statusCount('cancelled'),
            'upcoming' => $statusCount('upcoming'),
            'postponed' => $statusCount('postponed'),
        ]);
        $paymentOverview = $this->scopedPaymentOverview($paymentScopeFilter, [
            'completed' => $this->paymentCountByStatus(PaymentStatusEnum::Completed),
            'pending' => $payments['pendingPayments'],
            'failed' => $payments['failedPayments'],
            'cancelled' => $payments['cancelledPayments'],
            'refunded' => RefundRequest::where('status', RefundRequestStatusEnum::Approved)->count(),
            'pendingAmount' => $payments['pendingAmount'],
            'byStatus' => $payments['paymentsByStatus'],
        ]);

        return [
            'chartLabels' => $shortChartLabels,
            'userGrowthPercent' => $userGrowthPercent,
            'todaySummary' => $this->getTodaySummary(),
            'scopeFilter' => $scopeFilter,
            'paymentScopeFilter' => $paymentScopeFilter,
            'supportScopeFilter' => $supportScopeFilter,
            'kpis' => $kpis,
            'users' => [
                'total' => $users['totalUsers'],
                'active' => $users['activeUsers'],
                'inactive' => $users['inactiveUsers'],
                'verified' => $users['verifiedUsers'],
                'newThisMonth' => $users['newUsersThisMonth'],
                'byRole' => $users['usersByRole'],
                'registrationTrend' => $users['registrationTrend'],
                'recent' => $users['recentUsers'],
            ],
            'events' => [
                'total' => $admin['totalEvents'],
                'categories' => $admin['totalCategories'],
                'ongoing' => $statusCount('ongoing'),
                'completed' => $statusCount('completed'),
                'upcoming' => $statusCount('upcoming'),
                'postponed' => $statusCount('postponed'),
                'cancelled' => $statusCount('cancelled'),
                'unpublished' => $statusCount('unpublished'),
                'byStatus' => $admin['eventsByStatus'],
            ],
            'tickets' => $tickets,
            'revenue' => [
                'gross' => $payments['totalRevenue'],
                'net' => $payments['netRevenue'],
                'refunded' => $payments['totalRefunded'],
                'monthly' => $monthlyRevenue,
                'trend' => $payments['revenueTrend'],
            ],
            'payments' => $paymentOverview,
            'organizerPerformance' => $this->getOrganizerPerformance(),
            'platformAnalytics' => $platformAnalytics,
            'support' => $this->getSupportDashboardStats($supportScopeFilter),
            'miniCalendar' => app(DashboardCalendarWidgetService::class)->forAdmin(),
            'charts' => [
                'userGrowth' => $users['registrationTrend'],
                'revenue' => $payments['revenueTrend'],
                'ticketSalesByCategory' => $ticketSalesByCategory,
                'ticketSalesWeekly' => $weeklyTicketSales,
                'eventsByCategory' => $this->eventsByCategory(),
            ],
            'recentActivity' => $this->getSystemReports()['recentAuditLogs'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAllReports(): array
    {
        $admin = $this->getAdminReports();
        $users = $this->getUserReports();
        $payments = $this->getPaymentReports();
        $system = $this->getSystemReports();

        return [
            'admin' => $admin,
            'users' => $users,
            'payments' => $payments,
            'system' => $system,
            'chartLabels' => $this->lastSixMonthLabels(),
            'chartLabelsShort' => $this->lastSixMonthShortLabels(),
            'overview' => $this->getReportsOverview($admin, $users, $payments, $system),
        ];
    }

    /**
     * @param  array<string, mixed>  $admin
     * @param  array<string, mixed>  $users
     * @param  array<string, mixed>  $payments
     * @param  array<string, mixed>  $system
     * @return array<string, mixed>
     */
    private function getReportsOverview(array $admin, array $users, array $payments, array $system): array
    {
        $today = today();
        $organizerRoleId = UserRole::query()->where('name_en', UserRole::ORGANIZER)->value('id');

        $newUsersToday = User::query()->whereDate('created_at', $today)->count();
        $newEventsToday = Event::query()->whereDate('created_at', $today)->count();
        $newEventsThisWeek = Event::query()
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
        $ticketsSoldToday = ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereDate('created_at', $today)
            ->count();

        $pendingOrganizerApprovals = $organizerRoleId
            ? User::query()
                ->where('role_id', $organizerRoleId)
                ->where(function ($query) {
                    $query->where('is_active', false)
                        ->orWhereNull('email_verified_at');
                })
                ->count()
            : 0;

        $currentMonthRevenue = (float) Payment::query()
            ->where('status', PaymentStatusEnum::Completed)
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');
        $previousMonthRevenue = (float) Payment::query()
            ->where('status', PaymentStatusEnum::Completed)
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
            ])
            ->sum('amount');

        $revenueMoMPercent = $previousMonthRevenue > 0
            ? round((($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
            : ($currentMonthRevenue > 0 ? 100.0 : 0.0);

        $totalUsers = max(1, (int) $users['totalUsers']);
        $roleBreakdown = collect($users['usersByRole'])
            ->map(fn (array $role) => [
                'label' => $role['label'],
                'count' => (int) $role['count'],
                'percent' => round(((int) $role['count'] / $totalUsers) * 100, 1),
            ])
            ->values()
            ->all();

        return [
            'highlights' => [
                'newUsers' => $newUsersToday,
                'newEvents' => $newEventsToday,
                'ticketsSold' => $ticketsSoldToday,
                'pendingOrganizerApprovals' => $pendingOrganizerApprovals,
            ],
            'kpis' => [
                'totalUsers' => (int) $admin['totalUsers'],
                'usersToday' => $newUsersToday,
                'roleBreakdown' => $roleBreakdown,
                'totalEvents' => (int) $admin['totalEvents'],
                'eventsThisWeek' => $newEventsThisWeek,
                'platformRevenue' => (float) $payments['netRevenue'],
                'revenueMoMPercent' => $revenueMoMPercent,
                'ticketsSold' => (int) $admin['totalTicketsSold'],
                'ticketsToday' => $ticketsSoldToday,
            ],
            'userGrowth' => $users['registrationTrend'],
            'userDistribution' => $roleBreakdown,
            'revenueTrend' => [
                'labels' => $this->lastSixMonthShortLabels(),
                'values' => $payments['revenueTrend'],
                'formatted' => collect($this->lastSixMonthShortLabels())
                    ->zip($payments['revenueTrend'])
                    ->map(fn ($pair) => [
                        'month' => $pair[0],
                        'amount' => (float) $pair[1],
                        'label' => $this->formatCompactLkr((float) $pair[1]),
                    ])
                    ->values()
                    ->all(),
            ],
            'ticketSalesWeekly' => $this->weeklyTicketSales(),
            'recentUsers' => array_slice($users['recentUsers'], 0, 6),
            'organizerPerformance' => $this->getOrganizerPerformance(5),
            'recentPayments' => $this->getOverviewRecentPayments(6),
            'recentAuditLogs' => array_slice($system['recentAuditLogs'] ?? [], 0, 6),
            'platformStatus' => $this->getPlatformStatus(),
            'eventsByCategory' => $this->eventsByCategory(8),
        ];
    }

    /**
     * @return list<array{key: string, label: string, status: string, detail: string, online: bool}>
     */
    private function getPlatformStatus(): array
    {
        $databaseOnline = false;
        try {
            DB::connection()->getPdo();
            DB::select('select 1');
            $databaseOnline = true;
        } catch (\Throwable) {
            $databaseOnline = false;
        }

        $mailer = (string) config('mail.default', 'log');
        $mailConfigured = filled($mailer) && (
            $mailer === 'log'
            || filled(config("mail.mailers.{$mailer}.host"))
            || filled(config("mail.mailers.{$mailer}.transport"))
            || filled(config('mail.from.address'))
        );

        $stripeConnected = filled(config('services.stripe.secret'))
            && filled(config('services.stripe.key'));

        $storagePath = storage_path('app');
        $storageHealthy = is_dir($storagePath) && is_writable($storagePath);

        return [
            [
                'key' => 'database',
                'label' => 'Database',
                'status' => $databaseOnline ? 'Online' : 'Offline',
                'detail' => $databaseOnline ? 'Connection healthy' : 'Unable to connect',
                'online' => $databaseOnline,
                'icon' => 'bi-database',
            ],
            [
                'key' => 'email',
                'label' => 'Email Service',
                'status' => $mailConfigured ? 'Running' : 'Not configured',
                'detail' => $mailConfigured ? 'Mailer: '.$mailer : 'Mail settings missing',
                'online' => $mailConfigured,
                'icon' => 'bi-envelope',
            ],
            [
                'key' => 'stripe',
                'label' => 'Stripe Payments',
                'status' => $stripeConnected ? 'Connected' : 'Not connected',
                'detail' => $stripeConnected ? 'API keys present' : 'Stripe keys missing',
                'online' => $stripeConnected,
                'icon' => 'bi-credit-card',
            ],
            [
                'key' => 'storage',
                'label' => 'Storage',
                'status' => $storageHealthy ? 'Healthy' : 'Unavailable',
                'detail' => $storageHealthy ? 'Writable disk' : 'Storage not writable',
                'online' => $storageHealthy,
                'icon' => 'bi-hdd',
            ],
        ];
    }

    /**
     * @return list<array{customer: string, event: string, amount: float, status: string, statusLabel: string}>
     */
    private function getOverviewRecentPayments(int $limit = 6): array
    {
        return Payment::query()
            ->with(['user', 'ticketBookings.event'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function (Payment $payment) {
                $eventName = $payment->ticketBookings
                    ->map(fn ($booking) => $booking->event?->name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->first();

                $status = $payment->status->value;

                return [
                    'customer' => $payment->user?->full_name ?? 'Unknown',
                    'event' => $eventName ?: ($payment->purpose ?: '—'),
                    'amount' => (float) $payment->amount,
                    'status' => $status,
                    'statusLabel' => match ($status) {
                        'completed' => 'Paid',
                        'pending' => 'Pending',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($status),
                    },
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminReports(): array
    {
        $totalRevenue = (float) Payment::where('status', PaymentStatusEnum::Completed)->sum('amount');
        $totalRefunded = (float) RefundRequest::where('status', RefundRequestStatusEnum::Approved)->sum('refund_amount');

        return [
            'totalUsers' => User::count(),
            'totalEvents' => Event::count(),
            'totalHosts' => Host::count(),
            'totalCategories' => EventCategory::count(),
            'totalTicketsSold' => ticketBooking::count(),
            'totalRevenue' => $totalRevenue,
            'netRevenue' => $totalRevenue - $totalRefunded,
            'eventsByStatus' => $this->eventsByStatus(),
            'platformGrowth' => $this->monthlyCounts(User::class),
            'eventGrowth' => $this->monthlyCounts(Event::class),
            'ticketSalesTrend' => $this->monthlyCounts(ticketBooking::class),
            'topCategories' => Event::query()
                ->join('event_categories', 'events.category_id', '=', 'event_categories.id')
                ->select('event_categories.name as label', DB::raw('COUNT(*) as count'))
                ->groupBy('event_categories.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
                ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserReports(): array
    {
        $roleCounts = User::query()
            ->join('user_roles', 'users.role_id', '=', 'user_roles.id')
            ->select('user_roles.name_en as role', DB::raw('COUNT(*) as count'))
            ->groupBy('user_roles.name_en')
            ->pluck('count', 'role');

        return [
            'totalUsers' => User::count(),
            'activeUsers' => User::where('is_active', true)->count(),
            'inactiveUsers' => User::where('is_active', false)->count(),
            'verifiedUsers' => User::whereNotNull('email_verified_at')->count(),
            'unverifiedUsers' => User::whereNull('email_verified_at')->count(),
            'lockedUsers' => User::where('is_locked', true)->count(),
            'newUsersThisMonth' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            'usersByRole' => $this->formatRoleCounts($roleCounts),
            'registrationTrend' => $this->monthlyCounts(User::class),
            'recentUsers' => User::with('userRole')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (User $user) => [
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'role' => $this->roleDisplayLabel($user->userRole?->name_en),
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                    'verified' => (bool) $user->email_verified_at,
                    'joined' => $user->created_at?->diffForHumans(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getPaymentReports(): array
    {
        $completedRevenue = (float) Payment::where('status', PaymentStatusEnum::Completed)->sum('amount');
        $pendingAmount = (float) Payment::where('status', PaymentStatusEnum::Pending)->sum('amount');
        $totalRefunded = (float) RefundRequest::where('status', RefundRequestStatusEnum::Approved)->sum('refund_amount');
        $pendingRefunds = RefundRequest::where('status', RefundRequestStatusEnum::Pending)->count();

        return [
            'totalRevenue' => $completedRevenue,
            'pendingPayments' => Payment::where('status', PaymentStatusEnum::Pending)->count(),
            'pendingAmount' => $pendingAmount,
            'failedPayments' => Payment::where('status', PaymentStatusEnum::Failed)->count(),
            'cancelledPayments' => Payment::where('status', PaymentStatusEnum::Cancelled)->count(),
            'ticketsSold' => ticketBooking::count(),
            'totalRefunded' => $totalRefunded,
            'pendingRefunds' => $pendingRefunds,
            'netRevenue' => $completedRevenue - $totalRefunded,
            'revenueTrend' => $this->monthlyPaymentRevenue(),
            'paymentsByStatus' => $this->paymentsByStatus(),
            'paymentsByMethod' => $this->paymentsByMethod(),
            'recentPayments' => Payment::with('user')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Payment $payment) => [
                    'reference' => $payment->reference,
                    'user' => $payment->user?->full_name ?? 'Unknown',
                    'amount' => (float) $payment->amount,
                    'status' => $payment->status->value,
                    'method' => $payment->payment_method?->value ?? 'N/A',
                    'date' => $payment->created_at?->format('M d, Y'),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getSystemReports(): array
    {
        return [
            'totalAuditLogs' => AuditLog::count(),
            'auditLogsToday' => AuditLog::whereDate('created_at', today())->count(),
            'auditLogsThisWeek' => AuditLog::where('created_at', '>=', now()->startOfWeek())->count(),
            'totalInquiries' => Inquiry::count(),
            'totalComplaints' => Complaint::count(),
            'activityTrend' => $this->monthlyCounts(AuditLog::class),
            'auditByAction' => AuditLog::query()
                ->select('action', DB::raw('COUNT(*) as count'))
                ->groupBy('action')
                ->orderByDesc('count')
                ->limit(6)
                ->pluck('count', 'action')
                ->map(fn ($count, $action) => ['label' => ucfirst(str_replace('_', ' ', $action)), 'count' => (int) $count])
                ->values()
                ->all(),
            'recentAuditLogs' => AuditLog::with('user')
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (AuditLog $log) => [
                    'user' => $log->user?->full_name ?? 'System',
                    'action' => ucfirst(str_replace('_', ' ', $log->action)),
                    'model' => class_basename($log->model_type ?? 'N/A'),
                    'ip' => $log->ip_address ?? '—',
                    'time' => $log->created_at?->diffForHumans(),
                ])
                ->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthLabels(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('M Y'))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthShortLabels(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('M'))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     scope: string,
     *     organizers: list<array{id: int, name: string}>,
     *     events: list<array{id: int, name: string}>,
     *     selectedOrganizerId: int|null,
     *     selectedOrganizerName: string|null,
     *     selectedEventId: int|null,
     *     selectedEventName: string|null
     * }
     */
    private function resolveScopeFilter(?int $organizerId, ?int $eventId): array
    {
        $organizerRoleId = UserRole::query()->where('name_en', UserRole::ORGANIZER)->value('id');

        $organizers = $organizerRoleId
            ? User::query()
                ->where('role_id', $organizerRoleId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ])
                ->values()
                ->all()
            : [];

        $selectedOrganizerId = null;
        $selectedOrganizerName = null;
        $events = [];
        $selectedEventId = null;
        $selectedEventName = null;

        if ($organizerId) {
            $selectedOrganizer = collect($organizers)->firstWhere('id', $organizerId);
            if ($selectedOrganizer) {
                $selectedOrganizerId = (int) $selectedOrganizer['id'];
                $selectedOrganizerName = $selectedOrganizer['name'];

                $events = Event::query()
                    ->createdByOrganizer($selectedOrganizerId)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get(['id', 'name'])
                    ->map(fn (Event $event) => [
                        'id' => $event->id,
                        'name' => $event->name,
                    ])
                    ->values()
                    ->all();

                if ($eventId) {
                    $selectedEvent = collect($events)->firstWhere('id', $eventId);
                    if ($selectedEvent) {
                        $selectedEventId = (int) $selectedEvent['id'];
                        $selectedEventName = $selectedEvent['name'];
                    }
                }
            }
        }

        return [
            'scope' => $selectedEventId
                ? 'event'
                : ($selectedOrganizerId ? 'organizer' : 'global'),
            'organizers' => $organizers,
            'events' => $events,
            'selectedOrganizerId' => $selectedOrganizerId,
            'selectedOrganizerName' => $selectedOrganizerName,
            'selectedEventId' => $selectedEventId,
            'selectedEventName' => $selectedEventName,
        ];
    }

    /**
     * @param  array{
     *     scope: string,
     *     organizers: list<array{id: int, name: string}>,
     *     events: list<array{id: int, name: string}>,
     *     selectedOrganizerId: int|null,
     *     selectedOrganizerName: string|null,
     *     selectedEventId: int|null,
     *     selectedEventName: string|null
     * }  $scopeFilter
     * @param  array<string, mixed>  $global
     * @return list<array{label: string, value: string, sub: string, subClass: string, icon: string, accent: string}>
     */
    private function buildScopedKpis(array $scopeFilter, array $global): array
    {
        return match ($scopeFilter['scope']) {
            'event' => $this->buildEventScopeKpis(
                (int) $scopeFilter['selectedOrganizerId'],
                (int) $scopeFilter['selectedEventId'],
            ),
            'organizer' => $this->buildOrganizerScopeKpis(
                (int) $scopeFilter['selectedOrganizerId'],
                (string) $scopeFilter['selectedOrganizerName'],
            ),
            default => [
                [
                    'label' => 'Total Users',
                    'value' => number_format((int) $global['usersTotal']),
                    'sub' => (($global['userGrowthPercent'] >= 0 ? '+' : '').$global['userGrowthPercent']).'% vs last month',
                    'subClass' => ((float) $global['userGrowthPercent'] >= 0) ? 'text-emerald-600' : 'text-rose-600',
                    'icon' => 'bi-people',
                    'accent' => 'indigo',
                ],
                [
                    'label' => 'Total Events',
                    'value' => number_format((int) $global['eventsTotal']),
                    'sub' => $global['eventsOngoing'].' ongoing · '.$global['eventsCompleted'].' done',
                    'subClass' => 'text-slate-500',
                    'icon' => 'bi-calendar-event',
                    'accent' => 'blue',
                ],
                [
                    'label' => 'Platform Revenue',
                    'value' => 'LKR '.number_format((float) $global['revenueGross'], 0),
                    'sub' => 'Net LKR '.number_format((float) $global['revenueNet'], 0),
                    'subClass' => 'text-slate-500',
                    'icon' => 'bi-cash-stack',
                    'accent' => 'emerald',
                ],
                [
                    'label' => 'Tickets Sold',
                    'value' => number_format((int) $global['ticketsSold']),
                    'sub' => $global['ticketsReserved'].' reserved in carts',
                    'subClass' => 'text-slate-500',
                    'icon' => 'bi-ticket-perforated',
                    'accent' => 'cyan',
                ],
            ],
        };
    }

    /**
     * @return list<array{label: string, value: string, sub: string, subClass: string, icon: string, accent: string}>
     */
    private function buildOrganizerScopeKpis(int $organizerId, string $organizerName): array
    {
        $eventCount = Event::query()->createdByOrganizer($organizerId)->count();
        $statusCounts = Event::query()
            ->createdByOrganizer($organizerId)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $ticketsSold = (int) ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->count();

        $grossRevenue = (float) ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->sum('ticket_price');

        $attendees = (int) ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->distinct('user_id')
            ->count('user_id');

        $organizer = User::find($organizerId);
        $thisMonthRevenue = (float) ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('ticket_price');

        $lastMonthRevenue = (float) ticketBooking::query()
            ->where('status', BookingStatusEnum::Confirmed)
            ->whereHas('event', fn ($query) => $query->createdByOrganizer($organizerId))
            ->whereBetween('created_at', [
                now()->subMonthNoOverflow()->startOfMonth(),
                now()->subMonthNoOverflow()->endOfMonth(),
            ])
            ->sum('ticket_price');

        $suggestedGoal = max(50000, (int) (ceil(($lastMonthRevenue * 1.15) / 5000) * 5000));
        if ($suggestedGoal < $thisMonthRevenue) {
            $suggestedGoal = (int) (ceil(($thisMonthRevenue * 1.1) / 5000) * 5000);
        }
        $goal = (float) ($organizer?->monthly_revenue_goal ?: $suggestedGoal);
        $goalProgress = $goal > 0 ? min(100, round(($thisMonthRevenue / $goal) * 100, 1)) : 0;

        $ongoing = (int) ($statusCounts[Event::STATUS_ONGOING] ?? 0);
        $completed = (int) ($statusCounts[Event::STATUS_COMPLETED] ?? 0);

        return [
            [
                'label' => 'Events',
                'value' => number_format($eventCount),
                'sub' => $ongoing.' ongoing · '.$completed.' done',
                'subClass' => 'text-slate-500',
                'icon' => 'bi-calendar-event',
                'accent' => 'blue',
            ],
            [
                'label' => 'Organizer Revenue',
                'value' => 'LKR '.number_format($grossRevenue, 0),
                'sub' => $organizerName,
                'subClass' => 'text-slate-500',
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Tickets Sold',
                'value' => number_format($ticketsSold),
                'sub' => number_format($attendees).' unique attendees',
                'subClass' => 'text-slate-500',
                'icon' => 'bi-ticket-perforated',
                'accent' => 'cyan',
            ],
            [
                'label' => 'Monthly Goal',
                'value' => $goalProgress.'%',
                'sub' => 'LKR '.number_format($thisMonthRevenue, 0).' / '.number_format($goal, 0),
                'subClass' => $goalProgress >= 100 ? 'text-emerald-600' : 'text-slate-500',
                'icon' => 'bi-bullseye',
                'accent' => 'indigo',
            ],
        ];
    }

    /**
     * @return list<array{label: string, value: string, sub: string, subClass: string, icon: string, accent: string}>
     */
    private function buildEventScopeKpis(int $organizerId, int $eventId): array
    {
        $event = Event::query()
            ->createdByOrganizer($organizerId)
            ->withCount([
                'ticketBookings as tickets_sold' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
                'likes',
            ])
            ->withSum([
                'ticketBookings as revenue' => fn ($query) => $query->where('status', BookingStatusEnum::Confirmed),
            ], 'ticket_price')
            ->find($eventId);

        if (! $event) {
            return $this->buildOrganizerScopeKpis($organizerId, 'Organizer');
        }

        $ticketsSold = (int) $event->tickets_sold;
        $capacity = (int) $event->total_tickets;
        $remaining = max(0, $capacity - $ticketsSold);
        $fillRate = $capacity > 0 ? round(($ticketsSold / $capacity) * 100, 1) : 0;
        $revenue = (float) ($event->revenue ?? 0);
        $attendees = (int) ticketBooking::query()
            ->where('event_id', $event->id)
            ->where('status', BookingStatusEnum::Confirmed)
            ->distinct('user_id')
            ->count('user_id');

        $customGoal = $event->revenue_goal !== null ? (float) $event->revenue_goal : null;
        $suggestedGoal = max(10000, (int) (ceil(($revenue * 1.25) / 5000) * 5000));
        if ($suggestedGoal <= $revenue) {
            $suggestedGoal = (int) (ceil((($revenue + 10000) * 1.1) / 5000) * 5000);
        }
        $goal = $customGoal ?? (float) $suggestedGoal;
        $goalProgress = $goal > 0 ? min(100, round(($revenue / $goal) * 100, 1)) : 0;
        $goalReached = $revenue >= $goal && $goal > 0;

        return [
            [
                'label' => 'Event Revenue',
                'value' => 'LKR '.number_format($revenue, 0),
                'sub' => ucfirst((string) $event->status),
                'subClass' => 'text-slate-500',
                'icon' => 'bi-cash-stack',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Tickets Sold',
                'value' => number_format($ticketsSold).($capacity > 0 ? ' / '.number_format($capacity) : ''),
                'sub' => $fillRate.'% fill rate',
                'subClass' => 'text-slate-500',
                'icon' => 'bi-ticket-perforated',
                'accent' => 'cyan',
            ],
            [
                'label' => 'Attendees',
                'value' => number_format($attendees),
                'sub' => number_format($remaining).' tickets remaining',
                'subClass' => 'text-slate-500',
                'icon' => 'bi-people',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Revenue Goal',
                'value' => $goalProgress.'%',
                'sub' => $goalReached
                    ? 'Goal reached · LKR '.number_format($goal, 0)
                    : 'LKR '.number_format($revenue, 0).' / '.number_format($goal, 0),
                'subClass' => $goalReached ? 'text-emerald-600' : 'text-slate-500',
                'icon' => 'bi-bullseye',
                'accent' => 'blue',
            ],
        ];
    }

    /**
     * @param  array{
     *     scope: string,
     *     selectedOrganizerId: int|null,
     *     selectedEventId: int|null
     * }  $scopeFilter
     * @param  array{active: int, completed: int, cancelled: int, upcoming: int, postponed: int}  $global
     * @return array{active: int, completed: int, cancelled: int, upcoming: int, postponed: int}
     */
    private function scopedPlatformAnalytics(array $scopeFilter, array $global): array
    {
        if ($scopeFilter['scope'] === 'global') {
            return $global;
        }

        $query = Event::query();

        if ($scopeFilter['scope'] === 'event' && $scopeFilter['selectedEventId']) {
            $query->where('id', $scopeFilter['selectedEventId']);
        } elseif ($scopeFilter['selectedOrganizerId']) {
            $query->createdByOrganizer((int) $scopeFilter['selectedOrganizerId']);
        }

        $counts = (clone $query)
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'active' => (int) ($counts[Event::STATUS_ONGOING] ?? 0),
            'completed' => (int) ($counts[Event::STATUS_COMPLETED] ?? 0),
            'cancelled' => (int) ($counts[Event::STATUS_CANCELLED] ?? 0),
            'upcoming' => (int) ($counts[Event::STATUS_UPCOMING] ?? 0),
            'postponed' => (int) ($counts[Event::STATUS_POSTPONED] ?? 0),
        ];
    }

    /**
     * @param  array{
     *     scope: string,
     *     selectedOrganizerId: int|null,
     *     selectedOrganizerName: string|null,
     *     selectedEventId: int|null,
     *     selectedEventName: string|null
     * }  $scopeFilter
     * @param  array<string, mixed>  $global
     * @return array<string, mixed>
     */
    private function scopedPaymentOverview(array $scopeFilter, array $global): array
    {
        if ($scopeFilter['scope'] === 'global') {
            return $global;
        }

        $completed = $this->scopedPaymentCountByStatus($scopeFilter, PaymentStatusEnum::Completed);
        $pending = $this->scopedPaymentCountByStatus($scopeFilter, PaymentStatusEnum::Pending);
        $failed = $this->scopedPaymentCountByStatus($scopeFilter, PaymentStatusEnum::Failed);
        $cancelled = $this->scopedPaymentCountByStatus($scopeFilter, PaymentStatusEnum::Cancelled);
        $refunded = $this->scopedRefundedCount($scopeFilter);
        $pendingAmount = (float) $this->scopedPaymentsQuery($scopeFilter)
            ->where('status', PaymentStatusEnum::Pending)
            ->sum('amount');

        return [
            'completed' => $completed,
            'pending' => $pending,
            'failed' => $failed,
            'cancelled' => $cancelled,
            'refunded' => $refunded,
            'pendingAmount' => $pendingAmount,
            'byStatus' => [
                ['label' => 'Completed', 'count' => $completed],
                ['label' => 'Pending', 'count' => $pending],
                ['label' => 'Failed', 'count' => $failed],
                ['label' => 'Cancelled', 'count' => $cancelled],
            ],
        ];
    }

    /**
     * @param  array{scope: string, selectedOrganizerId: int|null, selectedEventId: int|null}  $scopeFilter
     */
    private function scopedPaymentCountByStatus(array $scopeFilter, PaymentStatusEnum $status): int
    {
        return $this->scopedPaymentsQuery($scopeFilter)
            ->where('status', $status)
            ->count();
    }

    /**
     * @param  array{scope: string, selectedOrganizerId: int|null, selectedEventId: int|null}  $scopeFilter
     */
    private function scopedRefundedCount(array $scopeFilter): int
    {
        $query = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Approved)
            ->whereHas('ticketBooking.event', function ($eventQuery) use ($scopeFilter) {
                if ($scopeFilter['scope'] === 'event' && $scopeFilter['selectedEventId']) {
                    $eventQuery->where('events.id', $scopeFilter['selectedEventId']);
                } elseif ($scopeFilter['selectedOrganizerId']) {
                    $eventQuery->where('events.created_by', $scopeFilter['selectedOrganizerId']);
                }
            });

        return $query->count();
    }

    /**
     * @param  array{scope: string, selectedOrganizerId: int|null, selectedEventId: int|null}  $scopeFilter
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Payment>
     */
    private function scopedPaymentsQuery(array $scopeFilter)
    {
        return Payment::query()
            ->whereHas('ticketBookings.event', function ($eventQuery) use ($scopeFilter) {
                if ($scopeFilter['scope'] === 'event' && $scopeFilter['selectedEventId']) {
                    $eventQuery->where('events.id', $scopeFilter['selectedEventId']);
                } elseif ($scopeFilter['selectedOrganizerId']) {
                    $eventQuery->where('events.created_by', $scopeFilter['selectedOrganizerId']);
                }
            });
    }

    /**
     * @return array{newOrganizers: int, newEvents: int, ticketsSold: int, supportRequests: int}
     */
    private function getTodaySummary(): array
    {
        $today = today();
        $organizerRoleId = UserRole::query()->where('name_en', UserRole::ORGANIZER)->value('id');

        return [
            'newOrganizers' => User::query()
                ->where('role_id', $organizerRoleId)
                ->whereDate('created_at', $today)
                ->count(),
            'newEvents' => Event::query()->whereDate('created_at', $today)->count(),
            'ticketsSold' => ticketBooking::query()
                ->where('status', BookingStatusEnum::Confirmed)
                ->whereDate('created_at', $today)
                ->count(),
            'supportRequests' => Inquiry::query()->whereDate('created_at', $today)->count()
                + Complaint::query()->whereDate('created_at', $today)->count(),
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function weeklyTicketSales(): array
    {
        return collect(range(3, 0))
            ->map(function (int $weeksAgo, int $index) {
                $start = now()->subWeeks($weeksAgo)->startOfWeek();
                $end = now()->subWeeks($weeksAgo)->endOfWeek();

                return [
                    'label' => 'Week '.($index + 1),
                    'count' => ticketBooking::query()
                        ->where('status', BookingStatusEnum::Confirmed)
                        ->whereBetween('created_at', [$start, $end])
                        ->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, events: int, ticketsSold: int, revenue: float, revenueLabel: string}>
     */
    private function getOrganizerPerformance(int $limit = 5): array
    {
        $organizerRoleId = UserRole::query()->where('name_en', UserRole::ORGANIZER)->value('id');

        if (! $organizerRoleId) {
            return [];
        }

        return User::query()
            ->where('users.role_id', $organizerRoleId)
            ->leftJoin('events', 'events.created_by', '=', 'users.id')
            ->leftJoin('ticket_bookings', function ($join) {
                $join->on('ticket_bookings.event_id', '=', 'events.id')
                    ->where('ticket_bookings.status', BookingStatusEnum::Confirmed->value);
            })
            ->select(
                'users.id',
                'users.first_name',
                'users.last_name',
                DB::raw('COUNT(DISTINCT events.id) as events_count'),
                DB::raw('COUNT(ticket_bookings.id) as tickets_sold'),
                DB::raw('COALESCE(SUM(ticket_bookings.ticket_price), 0) as revenue'),
            )
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('revenue')
            ->orderByDesc('tickets_sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'name' => trim(($row->first_name ?? '').' '.($row->last_name ?? '')) ?: 'Unknown',
                'events' => (int) $row->events_count,
                'ticketsSold' => (int) $row->tickets_sold,
                'revenue' => (float) $row->revenue,
                'revenueLabel' => $this->formatCompactLkr((float) $row->revenue),
            ])
            ->all();
    }

    private function formatCompactLkr(float $amount): string
    {
        if ($amount >= 1_000_000) {
            $value = round($amount / 1_000_000, 1);

            return 'LKR '.(fmod($value, 1.0) === 0.0 ? (int) $value : $value).'M';
        }

        if ($amount >= 1_000) {
            return 'LKR '.number_format($amount / 1_000, 0).'K';
        }

        return 'LKR '.number_format($amount, 0);
    }

    /**
     * @return list<string>
     */
    private function lastSixMonthKeys(): array
    {
        return collect(range(5, 0))
            ->map(fn (int $i) => now()->subMonths($i)->format('Y-m'))
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function monthlyCounts(string $modelClass): array
    {
        $keys = $this->lastSixMonthKeys();

        $counts = $modelClass::query()
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month');

        return collect($keys)
            ->map(fn (string $key) => (int) ($counts[$key] ?? 0))
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    private function monthlyPaymentRevenue(): array
    {
        $keys = $this->lastSixMonthKeys();

        $totals = Payment::query()
            ->where('status', PaymentStatusEnum::Completed)
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total")
            ->groupBy('month')
            ->pluck('total', 'month');

        return collect($keys)
            ->map(fn (string $key) => round((float) ($totals[$key] ?? 0), 2))
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function eventsByStatus(): array
    {
        $order = [
            Event::STATUS_UPCOMING,
            Event::STATUS_ONGOING,
            Event::STATUS_POSTPONED,
            Event::STATUS_COMPLETED,
            Event::STATUS_CANCELLED,
            Event::STATUS_UNPUBLISHED,
        ];

        $counts = Event::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return collect($order)
            ->map(fn (string $status) => [
                'label' => ucfirst($status),
                'count' => (int) ($counts[$status] ?? 0),
            ])
            ->filter(fn (array $row) => $row['count'] > 0 || $row['label'] === 'Postponed')
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function paymentsByStatus(): array
    {
        return Payment::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->map(fn ($row) => [
                'label' => ucfirst($row->status->value),
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function paymentsByMethod(): array
    {
        return Payment::query()
            ->whereNotNull('payment_method')
            ->select('payment_method', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get()
            ->map(fn ($row) => [
                'label' => ucfirst($row->payment_method->value),
                'count' => (int) $row->count,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function getTicketReports(): array
    {
        $reservationMinutes = (int) config('cart.reservation_minutes', 30);

        $reservedTickets = (int) CartItem::query()
            ->where(function ($query) use ($reservationMinutes) {
                $query->where('reserved_until', '>', now())
                    ->orWhere(function ($inner) use ($reservationMinutes) {
                        $inner->whereNull('reserved_until')
                            ->where('updated_at', '>=', now()->subMinutes($reservationMinutes));
                    });
            })
            ->sum('quantity');

        return [
            'sold' => ticketBooking::where('status', BookingStatusEnum::Confirmed)->count(),
            'cancelled' => ticketBooking::whereIn('status', [
                BookingStatusEnum::BookingCancelled,
                BookingStatusEnum::EventCancelled,
            ])->count(),
            'refunded' => ticketBooking::where('status', BookingStatusEnum::Refunded)->count(),
            'reserved' => $reservedTickets,
            'total' => ticketBooking::count(),
        ];
    }

    /**
     * @return array{
     *     scope: string,
     *     cros: list<array{id: int, name: string}>,
     *     events: list<array{id: int, name: string}>,
     *     selectedCroId: int|null,
     *     selectedCroName: string|null,
     *     selectedEventId: int|null,
     *     selectedEventName: string|null
     * }
     */
    private function resolveCroScopeFilter(?int $croId, ?int $eventId): array
    {
        $croRoleId = UserRole::query()->where('name_en', UserRole::CRO)->value('id');

        $cros = $croRoleId
            ? User::query()
                ->where('role_id', $croRoleId)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name'])
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'name' => $user->full_name,
                ])
                ->values()
                ->all()
            : [];

        $selectedCroId = null;
        $selectedCroName = null;
        $events = [];
        $selectedEventId = null;
        $selectedEventName = null;

        if ($croId) {
            $selectedCro = collect($cros)->firstWhere('id', $croId);
            if ($selectedCro) {
                $selectedCroId = (int) $selectedCro['id'];
                $selectedCroName = $selectedCro['name'];

                $eventIds = Inquiry::query()
                    ->where('assigned_to', $selectedCroId)
                    ->whereNotNull('event_id')
                    ->distinct()
                    ->pluck('event_id');

                $events = Event::query()
                    ->whereIn('id', $eventIds)
                    ->orderByDesc('date')
                    ->orderByDesc('id')
                    ->get(['id', 'name'])
                    ->map(fn (Event $event) => [
                        'id' => $event->id,
                        'name' => $event->name,
                    ])
                    ->values()
                    ->all();

                if ($eventId) {
                    $selectedEvent = collect($events)->firstWhere('id', $eventId);
                    if ($selectedEvent) {
                        $selectedEventId = (int) $selectedEvent['id'];
                        $selectedEventName = $selectedEvent['name'];
                    }
                }
            }
        }

        return [
            'scope' => $selectedEventId
                ? 'event'
                : ($selectedCroId ? 'cro' : 'global'),
            'cros' => $cros,
            'events' => $events,
            'selectedCroId' => $selectedCroId,
            'selectedCroName' => $selectedCroName,
            'selectedEventId' => $selectedEventId,
            'selectedEventName' => $selectedEventName,
        ];
    }

    /**
     * @param  array{
     *     scope: string,
     *     selectedCroId: int|null,
     *     selectedEventId: int|null
     * }|null  $scopeFilter
     * @return array<string, mixed>
     */
    private function getSupportDashboardStats(?array $scopeFilter = null): array
    {
        $openStatuses = [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress];
        $handledStatuses = [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed];
        $scope = $scopeFilter['scope'] ?? 'global';
        $croId = $scopeFilter['selectedCroId'] ?? null;
        $eventId = $scopeFilter['selectedEventId'] ?? null;

        $inquiryQuery = Inquiry::query();
        $complaintQuery = Complaint::query();

        if ($scope !== 'global' && $croId) {
            $inquiryQuery->where('assigned_to', $croId);
            $complaintQuery->where('assigned_to', $croId);
        }

        if ($scope === 'event' && $eventId) {
            $inquiryQuery->where('event_id', $eventId);
            // Complaints are not event-linked; hide them under event drill-down.
            $complaintQuery->whereRaw('1 = 0');
        }

        $totalInquiries = (clone $inquiryQuery)->count();
        $totalComplaints = (clone $complaintQuery)->count();
        $openInquiries = (clone $inquiryQuery)->whereIn('status', $openStatuses)->count();
        $openComplaints = (clone $complaintQuery)->whereIn('status', $openStatuses)->count();
        $pendingCount = $openInquiries + $openComplaints;
        $resolvedCount = (clone $inquiryQuery)->whereIn('status', $handledStatuses)->count()
            + (clone $complaintQuery)->whereIn('status', $handledStatuses)->count();
        $resolvedToday = (clone $inquiryQuery)
            ->whereIn('status', $handledStatuses)
            ->whereDate('updated_at', today())
            ->count()
            + (clone $complaintQuery)
                ->whereIn('status', $handledStatuses)
                ->whereDate('updated_at', today())
                ->count();

        $croRoleId = UserRole::query()->where('name_en', UserRole::CRO)->value('id');

        $croPerformance = User::query()
            ->where('role_id', $croRoleId)
            ->when($croId, fn ($query) => $query->where('id', $croId))
            ->get()
            ->map(function (User $cro) use ($openStatuses, $handledStatuses, $eventId) {
                $assignedInquiries = Inquiry::query()
                    ->where('assigned_to', $cro->id)
                    ->when($eventId, fn ($query) => $query->where('event_id', $eventId));
                $assignedComplaints = Complaint::query()
                    ->where('assigned_to', $cro->id)
                    ->when($eventId, fn ($query) => $query->whereRaw('1 = 0'));

                return [
                    'name' => $cro->full_name,
                    'totalAssigned' => (clone $assignedInquiries)->count() + (clone $assignedComplaints)->count(),
                    'handled' => (clone $assignedInquiries)->whereIn('status', $handledStatuses)->count()
                        + (clone $assignedComplaints)->whereIn('status', $handledStatuses)->count(),
                    'active' => (clone $assignedInquiries)->whereIn('status', $openStatuses)->count()
                        + (clone $assignedComplaints)->whereIn('status', $openStatuses)->count(),
                ];
            })
            ->sortByDesc('handled')
            ->values()
            ->all();

        return [
            'totalRequests' => $totalInquiries + $totalComplaints,
            'inquiries' => $totalInquiries,
            'complaints' => $totalComplaints,
            'openInquiries' => $openInquiries,
            'openComplaints' => $openComplaints,
            'resolvedToday' => $resolvedToday,
            'pending' => $pendingCount,
            'resolved' => $resolvedCount,
            'croPerformance' => $croPerformance,
        ];
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function eventsByCategory(int $limit = 8): array
    {
        return Event::query()
            ->join('event_categories', 'events.category_id', '=', 'event_categories.id')
            ->select('event_categories.name as label', DB::raw('COUNT(*) as count'))
            ->groupBy('event_categories.name')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
            ->values()
            ->all();
    }

    /**
     * @return list<array{label: string, count: int}>
     */
    private function ticketSalesByCategory(): array
    {
        return ticketBooking::query()
            ->join('events', 'ticket_bookings.event_id', '=', 'events.id')
            ->join('event_categories', 'events.category_id', '=', 'event_categories.id')
            ->where('ticket_bookings.status', BookingStatusEnum::Confirmed)
            ->select('event_categories.name as label', DB::raw('COUNT(*) as count'))
            ->groupBy('event_categories.name')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'count' => (int) $row->count])
            ->values()
            ->all();
    }

    private function paymentCountByStatus(PaymentStatusEnum $status): int
    {
        return Payment::where('status', $status)->count();
    }

    /**
     * @param  Collection<string, int>  $roleCounts
     * @return list<array{label: string, count: int}>
     */
    private function formatRoleCounts(Collection $roleCounts): array
    {
        return $roleCounts
            ->map(fn ($count, $role) => [
                'label' => $this->roleDisplayLabel($role),
                'count' => (int) $count,
            ])
            ->values()
            ->all();
    }

    private function roleDisplayLabel(?string $role): string
    {
        return match ($role) {
            UserRole::ADMIN => 'Administrator',
            UserRole::ORGANIZER => 'Organizer',
            UserRole::CRO => 'CRO',
            UserRole::ATTENDEE => 'Attendee',
            default => $role ? ucfirst($role) : 'Unknown',
        };
    }
}
