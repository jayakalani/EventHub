<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of events with filters.
     */
    public function index(Request $request)
    {
        $query = Event::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('place', 'like', "%{$search}%")
                    ->orWhereHas('host', function ($hostQuery) use ($search) {
                        $hostQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('eventCategory', function ($catQuery) use ($search) {
                        $catQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        } elseif ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $events = $query->paginate(10)->appends($request->all());

        return view('organizer.events.index', compact('events'));
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        $hosts = Host::all();
        $event_categories = EventCategory::all();

        $croUsers = User::whereHas('userRole', function ($q) {
            $q->where('name_en', UserRole::CRO);
        })->get();

        return view('organizer.events.create', compact('hosts', 'event_categories', 'croUsers'));
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'hosted_by' => 'required|exists:users,id',
            'category_id' => 'required|exists:event_categories,id',
            'date' => 'required|date',
            'time' => 'required',
            'place' => 'required|string|max:255',
            'no_of_seats' => 'required|integer|min:1',
            'description' => 'required|string',
            'contact_person' => 'required|exists:users,id',
            'cover' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/events/', $fileName);
        }

        Event::create([
            'name' => $validatedData['name'],
            'hosted_by' => $validatedData['hosted_by'],
            'category_id' => $validatedData['category_id'],
            'date' => $validatedData['date'],
            'time' => $validatedData['time'],
            'place' => $validatedData['place'],
            'no_of_seats' => $validatedData['no_of_seats'],
            'description' => $validatedData['description'],
            'contact_person' => $validatedData['contact_person'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id,
            'status' => 'upcoming',
        ]);

        return redirect()->route('organizer.events.index')->with('success', 'Event created successfully.');
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        $event->status = $request->status;
        $event->save();

        return back()->with('success', 'Event status updated successfully.');
    }

    /**
     * Show the form for editing an event.
     */
    public function edit(Event $event)
    {
        $hosts = Host::all();
        $event_categories = EventCategory::all();
        $croUsers = User::whereHas('userRole', function ($q) {
            $q->where('name_en', UserRole::CRO);
        })->get();

        return view('organizer.events.edit', compact('event', 'hosts', 'event_categories', 'croUsers'));
    }

    /**
     * Update an event.
     */
    public function update(Request $request, Event $event)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'hosted_by' => 'required|exists:users,id',
            'category_id' => 'required|exists:event_categories,id',
            'date' => 'required|date',
            'time' => 'required',
            'place' => 'required|string|max:255',
            'no_of_seats' => 'required|integer|min:1',
            'description' => 'required|string',
            'contact_person' => 'required|exists:users,id',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/events/', $fileName);
        }

        $event->name = $validatedData['name'];
        $event->hosted_by = $validatedData['hosted_by'];
        $event->category_id = $validatedData['category_id'];
        $event->date = $validatedData['date'];
        $event->time = $validatedData['time'];
        $event->place = $validatedData['place'];
        $event->no_of_seats = $validatedData['no_of_seats'];
        $event->description = $validatedData['description'];
        $event->contact_person = $validatedData['contact_person'];
        $event->cover = $fileName ?? $event->cover;
        // 'status'        => 'upcoming',

        $event->save();

        return redirect()->route('organizer.events.index')->with('success', 'Event updated successfully.');
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event)
    {
        if ($event->cover && Storage::disk('public')->exists($event->cover)) {
            Storage::disk('public')->delete($event->cover);
        }

        $event->delete();

        return redirect()->route('organizer.events.index')->with('success', "Event {$event->name} has been deleted.");
    }

    /**
     * Export events to CSV.
     */
    public function exportCsv()
    {
        $events = Event::all();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Place', 'Date', 'Time', 'Seats', 'Status', 'Created At'];

        foreach ($events as $event) {
            $csvData[] = [
                $event->id,
                $event->name,
                $event->place,
                $event->date,
                $event->time,
                $event->no_of_seats,
                $event->status,
                $event->created_at->format('Y-m-d H:i'),
            ];
        }

        $filename = 'events_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    /**
     * Export events to PDF.
     */
    public function exportPdf()
    {
        $events = Event::all();
        $pdf = \PDF::loadView('organizer.exports.events_pdf', compact('events'));

        return $pdf->download('events_'.now()->format('Ymd_His').'.pdf');
    }

    public function show(Event $event)
    {
        $seatCategories = $event->seatCategories; // relationship from Event model

        return view('organizer.events.show', compact('event', 'seatCategories'));
    }

    public function showexportPdf(Event $event)
    {
        $seatCategories = $event->seatCategories;
        $pdf = Pdf::loadView('organizer.events.exports.event_pdf', compact('event', 'seatCategories'));

        return $pdf->download($event->name.'_details.pdf');
    }

    public function showPublishedEvent(Event $event)
    {
        $event->load(['host', 'eventCategory', 'contactPerson', 'seatCategories']);

        $seatCategories = $event->seatCategories;

        return view('attendee.show', compact('event', 'seatCategories'));
    }
}
