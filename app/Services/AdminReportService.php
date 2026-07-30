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
    public function getDashboardData(): array
    {
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

        return [
            'chartLabels' => $shortChartLabels,
            'userGrowthPercent' => $userGrowthPercent,
            'todaySummary' => $this->getTodaySummary(),
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
                'cancelled' => $statusCount('cancelled'),
                'unpublished' => $statusCount('unpublished'),
                'byStatus' => $admin['eventsByStatus'],
            ],
            'tickets' => $this->getTicketReports(),
            'revenue' => [
                'gross' => $payments['totalRevenue'],
                'net' => $payments['netRevenue'],
                'refunded' => $payments['totalRefunded'],
                'monthly' => $monthlyRevenue,
                'trend' => $payments['revenueTrend'],
            ],
            'payments' => [
                'completed' => $this->paymentCountByStatus(PaymentStatusEnum::Completed),
                'pending' => $payments['pendingPayments'],
                'failed' => $payments['failedPayments'],
                'cancelled' => $payments['cancelledPayments'],
                'refunded' => RefundRequest::where('status', RefundRequestStatusEnum::Approved)->count(),
                'pendingAmount' => $payments['pendingAmount'],
                'byStatus' => $payments['paymentsByStatus'],
            ],
            'organizerPerformance' => $this->getOrganizerPerformance(),
            'platformAnalytics' => [
                'active' => $statusCount('ongoing'),
                'completed' => $statusCount('completed'),
                'cancelled' => $statusCount('cancelled'),
                'upcoming' => $statusCount('upcoming'),
            ],
            'support' => $this->getSupportDashboardStats(),
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
        return Event::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count, $status) => [
                'label' => ucfirst($status),
                'count' => (int) $count,
            ])
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
     * @return array<string, mixed>
     */
    private function getSupportDashboardStats(): array
    {
        $openStatuses = [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress];
        $handledStatuses = [SupportTicketStatusEnum::Resolved, SupportTicketStatusEnum::Closed];

        $totalInquiries = Inquiry::count();
        $totalComplaints = Complaint::count();
        $openInquiries = Inquiry::whereIn('status', $openStatuses)->count();
        $openComplaints = Complaint::whereIn('status', $openStatuses)->count();
        $pendingCount = $openInquiries + $openComplaints;
        $resolvedCount = Inquiry::whereIn('status', $handledStatuses)->count()
            + Complaint::whereIn('status', $handledStatuses)->count();
        $resolvedToday = Inquiry::query()
            ->whereIn('status', $handledStatuses)
            ->whereDate('updated_at', today())
            ->count()
            + Complaint::query()
                ->whereIn('status', $handledStatuses)
                ->whereDate('updated_at', today())
                ->count();

        $croRoleId = UserRole::query()->where('name_en', UserRole::CRO)->value('id');

        $croPerformance = User::query()
            ->where('role_id', $croRoleId)
            ->get()
            ->map(function (User $cro) use ($openStatuses, $handledStatuses) {
                $assignedInquiries = Inquiry::where('assigned_to', $cro->id);
                $assignedComplaints = Complaint::where('assigned_to', $cro->id);

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
