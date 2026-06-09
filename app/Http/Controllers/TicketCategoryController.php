<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\ticketCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ticketCategoryController extends Controller
{
    private function authorizeOrganizerEvent(Event $event): void
    {
        if (! $event->isOwnedByOrganizer(Auth::id())) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a listing of ticket categories for a specific event.
     */
    public function index(Event $event)
    {
        $this->authorizeOrganizerEvent($event);

        // Get all ticket categories for this event
        $ticketCategories = $event->ticketCategories;

        return view('organizer.ticket-categories.index', compact('event', 'ticketCategories'));
    }

    public function create($eventId)
    {
        $event = Event::createdByOrganizer(Auth::id())->findOrFail($eventId);
        $events = Event::createdByOrganizer(Auth::id())->get();

        return view('organizer.ticket-categories.create', compact('events', 'event'));
    }

    /**
     * Store a newly created ticket category.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'event_id' => ['required', 'exists:events,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'no_of_tickets' => ['required', 'integer', 'min:1'],
            'ticket_price' => ['required', 'numeric', 'min:0'],
            'ticket_color' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'booking_start' => ['nullable', 'date'],
            'booking_end' => ['nullable', 'date', 'after_or_equal:booking_start'],
        ]);

        $event = Event::createdByOrganizer(Auth::id())->findOrFail($validatedData['event_id']);

        if (! empty($validatedData['booking_start']) && $validatedData['booking_start'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_start' => "Booking start date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        if (! empty($validatedData['booking_end']) && $validatedData['booking_end'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_end' => "Booking end date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        $currentticketTotal = $event->ticketCategories()->sum('no_of_tickets');
        $proposedTotal = $currentticketTotal + $validatedData['no_of_tickets'];

        if ($proposedTotal > $event->no_of_tickets) {
            return redirect()->back()
                ->withErrors(['no_of_tickets' => "Total tickets across all categories cannot exceed the event's total of {$event->no_of_tickets}."])
                ->withInput();
        }

        $ticketCategory = ticketCategory::create([
            'event_id' => $validatedData['event_id'],
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

        return redirect()
            ->route('organizer.events.show', $validatedData['event_id'])
            ->with('status', 'New ticket Category was added successfully.');
    }

    /**
     * Show the form for editing a ticket category.
     */
    public function edit(Event $event, ticketCategory $ticketCategory)
    {
        $this->authorizeOrganizerEvent($event);

        return view('organizer.ticket-categories.edit', compact('event', 'ticketCategory'));
    }

    /**
     * Update the specified ticket category.
     */
    public function update(Request $request, Event $event, ticketCategory $ticketCategory)
    {
        $this->authorizeOrganizerEvent($event);

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

        if (! empty($validatedData['booking_start']) && $validatedData['booking_start'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_start' => "Booking start date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        if (! empty($validatedData['booking_end']) && $validatedData['booking_end'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_end' => "Booking end date cannot be after the event date ({$event->date})."])
                ->withInput();
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

        // Update fields
        $ticketCategory->name = $validatedData['name'];
        $ticketCategory->description = $validatedData['description'] ?? null;
        $ticketCategory->no_of_tickets = $validatedData['no_of_tickets'];
        $ticketCategory->ticket_price = $validatedData['ticket_price'];
        $ticketCategory->ticket_color = $validatedData['ticket_color'];
        $ticketCategory->is_active = $validatedData['is_active'] ?? $ticketCategory->is_active;
        $ticketCategory->booking_start = $validatedData['booking_start'] ?? null;
        $ticketCategory->booking_end = $validatedData['booking_end'] ?? null;

        // Adjust available tickets if total tickets changed
        if ($ticketCategory->isDirty('no_of_tickets')) {
            $difference = $validatedData['no_of_tickets'] - $ticketCategory->getOriginal('no_of_tickets');
            $ticketCategory->no_of_available_tickets += $difference;
            if ($ticketCategory->no_of_available_tickets < 0) {
                $ticketCategory->no_of_available_tickets = 0;
            }
        }

        $ticketCategory->save();

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'ticket Category updated successfully.');
    }

    /**
     * Delete a ticket category.
     */
    public function destroy(Event $event, ticketCategory $ticketCategory)
    {
        $this->authorizeOrganizerEvent($event);

        $ticketCategory->delete();

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'ticket Category deleted successfully.');
    }
}
