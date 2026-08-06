<?php

namespace App\Services;

use App\Enums\BookingStatusEnum;
use App\Models\ticketBooking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrganizerSalesService
{
    /**
     * Dashboard slice of recent retained-sale purchases (payment-grouped).
     *
     * @return list<array<string, mixed>>
     */
    public function recentPurchases(int $organizerId, int $limit = 8): array
    {
        return $this->paginate($organizerId, [], $limit, 1)->items();
    }

    /**
     * @param  array{search?: string|null, event_id?: int|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(int $organizerId, array $filters = [], int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        $page = $page ?? Paginator::resolveCurrentPage();
        $groupExpr = $this->groupKeyExpression();

        $keysPaginator = $this->bookingsQuery($organizerId, $filters)
            ->toBase()
            ->selectRaw("{$groupExpr} as group_key")
            ->selectRaw('MAX(ticket_bookings.created_at) as latest_at')
            ->groupByRaw($groupExpr)
            ->orderByDesc('latest_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $groupKeys = collect($keysPaginator->items())->pluck('group_key')->filter()->values();

        $sales = $this->loadGroupedSales($organizerId, $groupKeys);

        return new Paginator(
            $sales,
            $keysPaginator->total(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    /**
     * Aggregate stats for the current filters (confirmed + refund-declined purchases).
     *
     * @param  array{search?: string|null, event_id?: int|null, from_date?: string|null, to_date?: string|null}  $filters
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
            'revenue' => (float) (clone $query)->sum('ticket_price'),
            'unique_buyers' => (int) (clone $query)->distinct()->count('user_id'),
        ];
    }

    /**
     * All matching purchase groups for CSV/PDF export (no pagination).
     *
     * @param  array{search?: string|null, event_id?: int|null, from_date?: string|null, to_date?: string|null}  $filters
     * @return list<array<string, mixed>>
     */
    public function all(int $organizerId, array $filters = []): array
    {
        $groupExpr = $this->groupKeyExpression();

        $groupKeys = $this->bookingsQuery($organizerId, $filters)
            ->toBase()
            ->selectRaw("{$groupExpr} as group_key")
            ->selectRaw('MAX(ticket_bookings.created_at) as latest_at')
            ->groupByRaw($groupExpr)
            ->orderByDesc('latest_at')
            ->pluck('group_key');

        return $this->loadGroupedSales($organizerId, $groupKeys);
    }

    /**
     * @param  array{search?: string|null, event_id?: int|null, from_date?: string|null, to_date?: string|null}  $filters
     */
    private function bookingsQuery(int $organizerId, array $filters): Builder
    {
        $query = ticketBooking::query()
            ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer($organizerId))
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses());

        if (! empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
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
                $q->whereHas('user', function (Builder $userQuery) use ($search) {
                    $userQuery->where('email', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                })->orWhereHas('event', function (Builder $eventQuery) use ($search) {
                    $eventQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        return $query;
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
     * @param  Collection<int, string>  $groupKeys
     * @return list<array<string, mixed>>
     */
    private function loadGroupedSales(int $organizerId, Collection $groupKeys): array
    {
        if ($groupKeys->isEmpty()) {
            return [];
        }

        $paymentIds = [];
        $bookingIds = [];

        foreach ($groupKeys as $key) {
            if (str_starts_with((string) $key, 'p-')) {
                $paymentIds[] = (int) substr((string) $key, 2);
            } elseif (str_starts_with((string) $key, 'b-')) {
                $bookingIds[] = (int) substr((string) $key, 2);
            }
        }

        $bookings = ticketBooking::query()
            ->whereHas('event', fn (Builder $q) => $q->createdByOrganizer($organizerId))
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
            ->with(['user', 'event', 'ticketCategory', 'payment'])
            ->where(function (Builder $q) use ($paymentIds, $bookingIds) {
                if ($paymentIds !== []) {
                    $q->orWhereIn('payment_id', $paymentIds);
                }
                if ($bookingIds !== []) {
                    $q->orWhereIn('id', $bookingIds);
                }
            })
            ->latest()
            ->get()
            ->groupBy(fn (ticketBooking $booking) => $booking->payment_id
                ? 'p-'.$booking->payment_id
                : 'b-'.$booking->id);

        return $groupKeys
            ->map(function (string $key) use ($bookings) {
                $group = $bookings->get($key);

                return $group ? $this->mapPurchaseGroup($group) : null;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ticketBooking>  $group
     * @return array<string, mixed>
     */
    private function mapPurchaseGroup(Collection $group): array
    {
        /** @var ticketBooking $first */
        $first = $group->sortByDesc(fn (ticketBooking $b) => $b->created_at?->timestamp ?? 0)->first();

        $categoryBadges = $group
            ->groupBy(fn (ticketBooking $booking) => $booking->ticket_category_id ?? 'general')
            ->map(function ($items) {
                /** @var ticketBooking $item */
                $item = $items->first();
                $category = $item->ticketCategory;
                $count = $items->count();
                $name = $category?->name ?? 'General';

                return [
                    'name' => $name,
                    'label' => $name.($count > 1 ? ' ×'.$count : ''),
                    'count' => $count,
                    'color' => $category?->ticket_color ?: '#6366f1',
                ];
            })
            ->values()
            ->all();

        $categoryLines = collect($categoryBadges)->pluck('label')->all();
        $buyerEmail = $first->user?->email;
        $eventId = $first->event_id;

        return [
            'id' => $first->id,
            'payment_id' => $first->payment_id,
            'payment_reference' => $first->payment?->reference,
            'payment_method' => $first->payment?->payment_method?->value,
            'buyer' => $first->user?->full_name ?? 'Unknown',
            'email' => $buyerEmail ?? '—',
            'event' => $first->event?->name ?? '—',
            'event_id' => $eventId,
            'category' => $categoryLines[0] ?? 'General',
            'categories' => $categoryLines,
            'category_badges' => $categoryBadges,
            'quantity' => $group->count(),
            'amount' => round((float) $group->sum('ticket_price'), 2),
            'booked_at' => $first->created_at?->diffForHumans() ?? '—',
            'booked_at_formatted' => $first->created_at?->format('M d, Y H:i') ?? '—',
            'booked_raw' => $first->created_at?->toIso8601String(),
            'url' => $eventId
                ? route('organizer.events.show', $eventId)
                : route('organizer.events.index'),
            'event_url' => $eventId
                ? route('organizer.events.show', $eventId)
                : route('organizer.events.index'),
            'guests_url' => route('organizer.bookings.index', array_filter([
                'event_id' => $eventId,
                'search' => $buyerEmail,
            ])),
        ];
    }
}
