<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SeatCategory;
use App\Models\Event;

class SeatCategoryController extends Controller
{

    /**
     * Display a listing of seat categories for a specific event.
     */
    
    public function index(Event $event)
    {
        // Get all seat categories for this event
        $seatCategories = $event->seatCategories;

        return view('organizer.seat-categories.index', compact('event', 'seatCategories'));
    }

    public function create($eventId)
    {
        $event = Event::findOrFail($eventId);
        $events = Event::all();

        return view('organizer.seat-categories.create', compact('events', 'event'));
    }

    /**
     * Store a newly created seat category.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'event_id'        => ['required', 'exists:events,id'],
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'no_of_seats'     => ['required', 'integer', 'min:1'],
            'seat_price'      => ['required', 'numeric', 'min:0'],
            'ticket_color'    => ['required', 'string', 'max:255'],
            'is_active'       => ['boolean'],
            'booking_start'   => ['nullable', 'date'],
            'booking_end'     => ['nullable', 'date', 'after_or_equal:booking_start'],
        ]);

        $event = Event::findOrFail($validatedData['event_id']);

        if (!empty($validatedData['booking_start']) && $validatedData['booking_start'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_start' => "Booking start date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        if (!empty($validatedData['booking_end']) && $validatedData['booking_end'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_end' => "Booking end date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        $currentSeatTotal = $event->seatCategories()->sum('no_of_seats');
        $proposedTotal = $currentSeatTotal + $validatedData['no_of_seats'];

        if ($proposedTotal > $event->no_of_seats) {
            return redirect()->back()
                ->withErrors(['no_of_seats' => "Total seats across all categories cannot exceed the event's total of {$event->no_of_seats}."])
                ->withInput();
        }

        $seatCategory = SeatCategory::create([
            'event_id'              => $validatedData['event_id'],
            'name'                  => $validatedData['name'],
            'description'           => $validatedData['description'] ?? null,
            'no_of_seats'           => $validatedData['no_of_seats'],
            'no_of_available_seats' => $validatedData['no_of_seats'], // initially all seats available
            'seat_price'            => $validatedData['seat_price'],
            'ticket_color'          => $validatedData['ticket_color'],
            'is_active'             => $validatedData['is_active'] ?? true,
            'booking_start'         => $validatedData['booking_start'] ?? null,
            'booking_end'           => $validatedData['booking_end'] ?? null,
        ]);

        return redirect()
            ->route('organizer.events.show', $validatedData['event_id'])
            ->with('status', 'New Seat Category was added successfully.');
    }

    /**
     * Show the form for editing a seat category.
     */
    public function edit(Event $event, SeatCategory $seatCategory)
    {
        return view('organizer.seat-categories.edit', compact('event', 'seatCategory'));
    }

    /**
     * Update the specified seat category.
     */
    public function update(Request $request, Event $event, SeatCategory $seatCategory)
    {
        $validatedData = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string'],
            'no_of_seats'   => ['required', 'integer', 'min:1'],
            'seat_price'    => ['required', 'numeric', 'min:0'],
            'ticket_color'  => ['required', 'string', 'max:255'],
            'is_active'     => ['boolean'],
            'booking_start' => ['nullable', 'date'],
            'booking_end'   => ['nullable', 'date', 'after_or_equal:booking_start'],
        ]);

        if (!empty($validatedData['booking_start']) && $validatedData['booking_start'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_start' => "Booking start date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        if (!empty($validatedData['booking_end']) && $validatedData['booking_end'] > $event->date) {
            return redirect()->back()
                ->withErrors(['booking_end' => "Booking end date cannot be after the event date ({$event->date})."])
                ->withInput();
        }

        $existingTotalWithoutCurrent = $event->seatCategories()
            ->where('id', '!=', $seatCategory->id)
            ->sum('no_of_seats');

        $proposedTotal = $existingTotalWithoutCurrent + $validatedData['no_of_seats'];

        if ($proposedTotal > $event->no_of_seats) {
            return redirect()->back()
                ->withErrors(['no_of_seats' => "Total seats across all categories cannot exceed the event's total of {$event->no_of_seats}."])
                ->withInput();
        }

        // Update fields
        $seatCategory->name        = $validatedData['name'];
        $seatCategory->description = $validatedData['description'] ?? null;
        $seatCategory->no_of_seats = $validatedData['no_of_seats'];
        $seatCategory->seat_price  = $validatedData['seat_price'];
        $seatCategory->ticket_color = $validatedData['ticket_color'];
        $seatCategory->is_active   = $validatedData['is_active'] ?? $seatCategory->is_active;
        $seatCategory->booking_start = $validatedData['booking_start'] ?? null;
        $seatCategory->booking_end   = $validatedData['booking_end'] ?? null;

        // Adjust available seats if total seats changed
        if ($seatCategory->isDirty('no_of_seats')) {
            $difference = $validatedData['no_of_seats'] - $seatCategory->getOriginal('no_of_seats');
            $seatCategory->no_of_available_seats += $difference;
            if ($seatCategory->no_of_available_seats < 0) {
                $seatCategory->no_of_available_seats = 0;
            }
        }

        $seatCategory->save();

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'Seat Category updated successfully.');
    }

    /**
     * Delete a seat category.
     */
    public function destroy(Event $event, SeatCategory $seatCategory)
    {
        $seatCategory->delete();

        return redirect()
            ->route('organizer.events.show', $event->id)
            ->with('success', 'Seat Category deleted successfully.');
    }

    
}
