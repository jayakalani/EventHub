<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event; // make sure you have an Event model
use Illuminate\Support\Facades\Auth;


class OrganizerEventController extends Controller
{
    /**
     * Show the event creation form.
     */
    public function create()
    {
        return view('organizer.events.create');
    }

    /**
     * Handle form submission and save event.
     */
    public function store(Request $request)
    {
            $validated = $request->validate([
        'name'          => 'required|string|max:255',
        'hosted_by'     => 'required|exists:hosts,id',
        'category_id'   => 'required|exists:event_categories,id',
        'date'          => 'required|date',
        'time'          => 'required',
        'place'         => 'required|string|max:255',
        'no_of_seats'   => 'nullable|integer|min:0',
        'description'   => 'nullable|string',
        'contact_person'=> 'required|exists:users,id',
        'cover'         => 'nullable|string',
    ]);

    $validated['created_by'] = Auth::id();
    Event::create($validated);


    return redirect()->route('organizer.events.create')
                         ->with('status', 'event-created');
    }
}

