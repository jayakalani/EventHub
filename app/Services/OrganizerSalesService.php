<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Enums\RefundRequestStatusEnum;
use App\Models\ticketBooking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrganizerSalesService
{
    /**
     * Dashboard slice of recent retained-sale tickets.
     *
     * @return list<array<string, mixed>>
     */
    public function recentPurchases(int $organizerId, int $limit = 8): array
    {
        return $this->paginate($organizerId, [], $limit, 1)->items();
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     event_id?: int|null,
     *     ticket_category?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(int $organizerId, array $filters = [], int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        $paginator = $this->bookingsQuery($organizerId, $filters)
            ->with(['user', 'event', 'ticketCategory', 'refundRequest'])
            ->latest('ticket_bookings.created_at')
            ->latest('ticket_bookings.id')
            ->paginate($perPage, ['*'], 'page', $page);

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (ticketBooking $booking) => $this->mapTicketSale($booking))
        );

        return $paginator;
    }

    /**
     * Aggregate stats for the current filters.
     * Revenue uses retained sale amounts (full price, or remaining balance after partial refunds).
     *
     * @param  array{
     *     search?: string|null,
     *     event_id?: int|null,
     *     ticket_category?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return array{purchases: int, tickets: int, revenue: float, unique_buyers: int}
     */
    public function stats(int $organizerId, array $filters = []): array
    {
        $query = $this->bookingsQuery($organizerId, $filters);
        $groupExpr = $this->groupKeyExpression();

        $purchases = (int) (clone $query)
            ->toBase()
            ->selectRaw("COUNT(DISTINCT {$groupExpr}) as aggregate")
            ->value('aggregate');

        return [
            'purchases' => $purchases,
            'tickets' => (clone $query)->count(),
            'revenue' => $this->sumRetainedRevenue(clone $query),
            'unique_buyers' => (int) (clone $query)->distinct()->count('user_id'),
        ];
    }

    /**
     * All matching tickets for CSV/PDF export (no pagination).
     *
     * @param  array{
     *     search?: string|null,
     *     event_id?: int|null,
     *     ticket_category?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     * @return list<array<string, mixed>>
     */
    public function all(int $organizerId, array $filters = []): array
    {
        return $this->bookingsQuery($organizerId, $filters)
            ->with(['user', 'event', 'ticketCategory', 'refundRequest'])
            ->latest('ticket_bookings.created_at')
            ->latest('ticket_bookings.id')
            ->get()
            ->map(fn (ticketBooking $booking) => $this->mapTicketSale($booking))
            ->values()
            ->all();
    }

    /**
     * @param  array{
     *     search?: string|null,
     *     event_id?: int|null,
     *     ticket_category?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }  $filters
     */
    private function bookingsQuery(int $organizerId, array $filters): Builder
    {
        $query = ticketBooking::query()
            ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer($organizerId))
            ->where(function (Builder $statusQuery) {
                $statusQuery
                    ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
                    ->orWhere(function (Builder $partialRefundQuery) {
                        $partialRefundQuery
                            ->where('status', BookingStatusEnum::Refunded)
                            ->whereHas('refundRequest', function (Builder $refundQuery) {
                                $refundQuery
                                    ->where('status', RefundRequestStatusEnum::Approved)
                                    ->where(function (Builder $partial) {
                                        $partial
                                            ->where('refund_percentage', '<', 100)
                                            ->orWhereColumn(
                                                'refund_requests.refund_amount',
                                                '<',
                                                'ticket_bookings.ticket_price'
                                            );
                                    });
                            });
                    });
            });

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (! empty($filters['ticket_category'])) {
            $categoryName = $filters['ticket_category'];
            $query->whereHas('ticketCategory', function (Builder $categoryQuery) use ($categoryName) {
                $categoryQuery->where('name', $categoryName);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('ticket_bookings.created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('ticket_bookings.created_at', '<=', $filters['to_date']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('event', function (Builder $eventQuery) use ($search) {
                        $eventQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ticketCategory', function (Builder $categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    private function sumRetainedRevenue(Builder $query): float
    {
        $refunded = BookingStatusEnum::Refunded->value;
        $approved = RefundRequestStatusEnum::Approved->value;

        $aggregate = (clone $query)
            ->toBase()
            ->selectRaw("
                SUM(
                    CASE
                        WHEN ticket_bookings.status = ? THEN GREATEST(
                            0,
                            ticket_bookings.ticket_price - COALESCE((
                                SELECT rr.refund_amount
                                FROM refund_requests rr
                                WHERE rr.ticket_booking_id = ticket_bookings.id
                                    AND rr.status = ?
                                LIMIT 1
                            ), 0)
                        )
                        ELSE ticket_bookings.ticket_price
                    END
                ) as aggregate
            ", [$refunded, $approved])
            ->value('aggregate');

        return round((float) $aggregate, 2);
    }

    private function groupKeyExpression(): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return "CASE WHEN payment_id IS NOT NULL THEN 'p-' || payment_id ELSE 'b-' || id END";
        }

        return "CASE WHEN payment_id IS NOT NULL THEN CONCAT('p-', payment_id) ELSE CONCAT('b-', id) END";
    }

    /**
     * @return array<string, mixed>
     */
    private function mapTicketSale(ticketBooking $booking): array
    {
        $category = $booking->ticketCategory;
        $categoryName = $category?->name ?? 'General';
        $categoryColor = $category?->ticket_color ?: '#6366f1';
        $eventId = $booking->event_id;
        $buyer = $booking->user?->full_name ?? 'Unknown';
        $checkedIn = $booking->isCheckedIn();
        $originalAmount = round((float) $booking->ticket_price, 2);
        $amount = $booking->retainedSaleAmount();
        $refundAmount = $booking->approvedRefundAmount();
        $isPartialRefund = $booking->isPartiallyRefunded();

        return [
            'id' => $booking->id,
            'ticket_number' => $booking->ticket_number,
            'buyer' => $buyer,
            'email' => $booking->user?->email ?? '—',
            'event' => $booking->event?->name ?? '—',
            'event_id' => $eventId,
            'category' => $categoryName,
            'category_color' => $categoryColor,
            'category_badges' => [[
                'name' => $categoryName,
                'label' => $categoryName,
                'count' => 1,
                'color' => $categoryColor,
            ]],
            'quantity' => 1,
            'amount' => $amount,
            'original_amount' => $originalAmount,
            'refund_amount' => $refundAmount,
            'is_partial_refund' => $isPartialRefund,
            'booked_at' => $booking->created_at?->diffForHumans() ?? '—',
            'booked_at_formatted' => $booking->created_at?->format('M d, Y H:i') ?? '—',
            'booked_raw' => $booking->created_at?->toIso8601String(),
            'checked_in' => $checkedIn,
            'checked_in_at' => $booking->checked_in_at?->format('M d, H:i'),
            'check_in_status' => $checkedIn ? 'Checked In' : 'Not checked in',
            'check_in_badge_classes' => $checkedIn
                ? 'bg-sky-100 text-sky-800'
                : 'bg-slate-100 text-slate-600',
            'status' => $booking->displayStatusLabel(),
            'status_badge_classes' => $booking->displayStatusBadgeClasses(),
            'status_balance_label' => $isPartialRefund
                ? 'Balance LKR '.number_format($amount, 2)
                : null,
            'url' => route('organizer.bookings.show', $booking),
            'event_url' => $eventId
                ? route('organizer.events.show', $eventId)
                : route('organizer.events.index'),
        ];
    }
}
