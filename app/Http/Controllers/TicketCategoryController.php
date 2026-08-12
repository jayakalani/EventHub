<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatusEnum;
use App\Models\Event;
use App\Models\ticketCategory;
use App\Services\AdminNotificationService;
use App\Services\CartInventoryService;
use App\Services\EventNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ticketCategoryController extends Controller
{
    public function __construct(
        protected CartInventoryService $cartInventoryService,
    ) {}

    /**
     * Display a listing of ticket categories for a specific event.
     */
    public function index(Event $event)
    {
        $this->authorize('view', $event);

        // Get all ticket categories for this event
        $ticketCategories = $event->ticketCategories;

        return view('organizer.ticket-categories.index', compact('event', 'ticketCategories'));
    }

    public function create(Event $event)
    {
        $this->authorize('create', ticketCategory::class);
        $this->authorize('update', $event);

        return view('organizer.ticket-categories.create', compact('event'));
    }

    /**
     * Store a newly created ticket category.
     */
    public function store(Request $request, Event $event)
    {
        $this->authorize('create', ticketCategory::class);
        $this->authorize('update', $event);

        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'no_of_tickets' => ['required', 'integer', 'min:1'],
            'ticket_price' => ['required', 'numeric', 'min:0'],
            'ticket_color' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'booking_start' => ['nullable', 'date'],
            'booking_end' => ['nullable', 'date', 'after_or_equal:booking_start'],
        ]);

        if ($windowErrors = $this->bookingWindowErrors($event, $validatedData)) {
            return redirect()->back()->withErrors($windowErrors)->withInput();
        }

        $currentticketTotal = $event->ticketCategories()->sum('no_of_tickets');
        $proposedTotal = $currentticketTotal + $validatedData['no_of_tickets'];

        if ($proposedTotal > $event->no_of_tickets) {
            return redirect()->back()
                ->withErrors(['no_of_tickets' => "Total tickets across all categories cannot exceed the event's total of {$event->no_of_tickets}."])
                ->withInput();
        }

        $ticketCategory = ticketCategory::create([
            'event_id' => $event->id,
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'no_of_tickets' => $validatedData['no_of_tickets'],
            'no_of_available_tickets' => $validatedData['no_of_tickets'], // initially all tickets available
            'ticket_price' => $validatedData['ticket_price'],
            'ticket_color' => $validatedData['ticket_color'],
            'is_active' => $validatedData['is_active'] ?? true,
            'booking_start' => $validatedData['booking_start'] ?? null,
            'booking_end' => $validatedData['booking_end'] ?? null,
        ]);

        if ($ticketCategory->isSalesOpenNow()) {
            $event->unsetRelation('ticketCategories');
            app(EventNotificationService::class)->notifyTicketSalesOpened($event);
        }

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('status', 'New ticket Category was added successfully.');
    }

    /**
     * Show the form for editing a ticket category.
     */
    public function edit(Event $event, ticketCategory $ticketCategory)
    {
        $this->authorize('update', $event);
        $this->authorize('update', $ticketCategory);

        if ($ticketCategory->event_id !== $event->id) {
            abort(404);
        }

        $holdSummary = $this->cartInventoryService->holdSummaryForCategory((int) $ticketCategory->id);
        $soldCount = $this->committedSoldCount($ticketCategory);
        $heldCount = (int) ($holdSummary['held'] ?? 0);
        $minTickets = max(1, $soldCount + $heldCount);

        return view('organizer.ticket-categories.edit', compact(
            'event',
            'ticketCategory',
            'holdSummary',
            'soldCount',
            'heldCount',
            'minTickets',
        ));
    }

    /**
     * Update the specified ticket category.
     */
    public function update(Request $request, Event $event, ticketCategory $ticketCategory)
    {
        $this->authorize('update', $event);
        $this->authorize('update', $ticketCategory);

        if ($ticketCategory->event_id !== $event->id) {
            abort(404);
        }

        $holdSummary = $this->cartInventoryService->holdSummaryForCategory((int) $ticketCategory->id);
        $soldCount = $this->committedSoldCount($ticketCategory);
        $heldCount = (int) ($holdSummary['held'] ?? 0);
        $minTickets = max(1, $soldCount + $heldCount);
        $priceLocked = $soldCount > 0;

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'no_of_tickets' => ['required', 'integer', 'min:'.$minTickets],
            'ticket_color' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'booking_start' => ['nullable', 'date'],
            'booking_end' => ['nullable', 'date', 'after_or_equal:booking_start'],
        ];

        if (! $priceLocked) {
            $rules['ticket_price'] = ['required', 'numeric', 'min:0'];
        }

        $validatedData = $request->validate($rules, [
            'no_of_tickets.min' => $this->minTicketsValidationMessage($minTickets, $soldCount, $heldCount),
        ]);

        if ($windowErrors = $this->bookingWindowErrors($event, $validatedData)) {
            return redirect()->back()->withErrors($windowErrors)->withInput();
        }

        $existingTotalWithoutCurrent = $event->ticketCategories()
            ->where('id', '!=', $ticketCategory->id)
            ->sum('no_of_tickets');

        $proposedTotal = $existingTotalWithoutCurrent + $validatedData['no_of_tickets'];

        if ($proposedTotal > $event->no_of_tickets) {
            return redirect()->back()
                ->withErrors(['no_of_tickets' => "Total tickets across all categories cannot exceed the event's total of {$event->no_of_tickets}."])
                ->withInput();
        }

        $wasSalesOpen = $ticketCategory->isSalesOpenNow();

        // Update fields
        $ticketCategory->name = $validatedData['name'];
        $ticketCategory->description = $validatedData['description'] ?? null;
        $ticketCategory->no_of_tickets = $validatedData['no_of_tickets'];
        if (! $priceLocked) {
            $ticketCategory->ticket_price = $validatedData['ticket_price'];
        }
        $ticketCategory->ticket_color = $validatedData['ticket_color'];
        $ticketCategory->is_active = $validatedData['is_active'] ?? $ticketCategory->is_active;
        $ticketCategory->booking_start = $validatedData['booking_start'] ?? null;
        $ticketCategory->booking_end = $validatedData['booking_end'] ?? null;

        // Adjust available tickets if total tickets changed
        if ($ticketCategory->isDirty('no_of_tickets')) {
            $difference = $validatedData['no_of_tickets'] - $ticketCategory->getOriginal('no_of_tickets');
            $ticketCategory->no_of_available_tickets += $difference;

            $maxAvailable = max(0, $validatedData['no_of_tickets'] - $soldCount - $heldCount);
            if ($ticketCategory->no_of_available_tickets > $maxAvailable) {
                $ticketCategory->no_of_available_tickets = $maxAvailable;
            }
            if ($ticketCategory->no_of_available_tickets < 0) {
                $ticketCategory->no_of_available_tickets = 0;
            }
        }

        $ticketCategory->save();

        if (! $wasSalesOpen && $ticketCategory->fresh()->isSalesOpenNow()) {
            $event->unsetRelation('ticketCategories');
            app(EventNotificationService::class)->notifyTicketSalesOpened($event);
        }

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'ticket Category updated successfully.');
    }

    /**
     * Delete a ticket category.
     */
    public function destroy(Event $event, ticketCategory $ticketCategory)
    {
        $this->authorize('update', $event);
        $this->authorize('delete', $ticketCategory);

        if ($ticketCategory->event_id !== $event->id) {
            abort(404);
        }

        $categoryName = $ticketCategory->name;

        if ($ticketCategory->hasBookingHistory()) {
            $ticketCategory->delete();

            app(AdminNotificationService::class)->notifyOrganizerCategoryDeleted(
                $event,
                $ticketCategory,
                Auth::user(),
            );

            return redirect()
                ->route('organizer.events.show', $event->id)
                ->with('success', "Ticket category {$categoryName} has been archived. Booking history was preserved.");
        }

        $this->cartInventoryService->releaseAndDeleteMany(
            $ticketCategory->cartItems()->get()
        );

        $ticketCategory->forceDelete();

        app(AdminNotificationService::class)->notifyOrganizerCategoryDeleted(
            $event,
            $ticketCategory,
            Auth::user(),
        );

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'ticket Category deleted successfully.');
    }

    /**
     * Compare booking windows to the event date using date-only values.
     * Skipped when the event schedule is TBA / date is blank.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>|null
     */
    private function bookingWindowErrors(Event $event, array $data): ?array
    {
        if ($event->hasDateYetToBeScheduled() || blank($event->date)) {
            return null;
        }

        $eventDate = Carbon::parse($event->date)->toDateString();
        $errors = [];

        foreach (['booking_start', 'booking_end'] as $field) {
            if (empty($data[$field])) {
                continue;
            }

            $windowDate = Carbon::parse($data[$field])->toDateString();

            if ($windowDate > $eventDate) {
                $errors[$field] = "Booking window cannot be after the event date ({$eventDate}).";
            }
        }

        return $errors === [] ? null : $errors;
    }

    /**
     * Bookings that still occupy capacity for this category.
     */
    private function committedSoldCount(ticketCategory $ticketCategory): int
    {
        return $ticketCategory->ticketBookings()
            ->whereIn('status', BookingStatusEnum::retainedSaleStatuses())
            ->count();
    }

    private function minTicketsValidationMessage(int $minTickets, int $soldCount, int $heldCount): string
    {
        if ($soldCount + $heldCount < 1) {
            return "Total tickets must be at least {$minTickets}.";
        }

        if ($heldCount > 0) {
            return "Total tickets cannot be less than {$minTickets} ({$soldCount} sold + {$heldCount} currently in carts).";
        }

        return "Total tickets cannot be less than {$minTickets} (already sold).";
    }
}
