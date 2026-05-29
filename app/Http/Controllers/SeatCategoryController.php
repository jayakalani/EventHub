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
            ->route('organizer.seat-categories.index', $validatedData['event_id'])
            ->with('status', 'New Seat Category was added successfully.');
    }

    /**
     * Update an existing seat category.
     */
    public function update(Request $request, SeatCategory $seatCategory)
    {
        $validatedData = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string'],
            'no_of_seats'     => ['required', 'integer', 'min:1'],
            'seat_price'      => ['required', 'numeric', 'min:0'],
            'ticket_color'    => ['required', 'string', 'max:255'],
            'is_active'       => ['boolean'],
            'booking_start'   => ['nullable', 'date'],
            'booking_end'     => ['nullable', 'date', 'after_or_equal:booking_start'],
        ]);

        $seatCategory->update([
            'name'                  => $validatedData['name'],
            'description'           => $validatedData['description'] ?? null,
            'no_of_seats'           => $validatedData['no_of_seats'],
            'no_of_available_seats' => min($seatCategory->no_of_available_seats, $validatedData['no_of_seats']),
            'seat_price'            => $validatedData['seat_price'],
            'ticket_color'          => $validatedData['ticket_color'],
            'is_active'             => $validatedData['is_active'] ?? $seatCategory->is_active,
            'booking_start'         => $validatedData['booking_start'] ?? null,
            'booking_end'           => $validatedData['booking_end'] ?? null,
        ]);

        return redirect()
            ->route('organizer.seat-categories.index', $seatCategory->event_id)
            ->with('status', 'Seat Category updated successfully.');
    }

    /**
     * Delete a seat category.
     */
    public function destroy(SeatCategory $seatCategory)
    {
        $eventId = $seatCategory->event_id;
        $seatCategory->delete();

        return redirect()
            ->route('organizer.seat-categories.index', $eventId)
            ->with('status', 'Seat Category deleted successfully.');
    }
}
