<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Event;
use App\Enums\BookingStatusEnum;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use App\Services\EventCancellationService;
use App\Services\EventCompletionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    public function __construct(
        protected EventCancellationService $eventCancellationService,
        protected EventCompletionService $eventCompletionService,
    ) {}

    /**
     * Base query scoped to the logged-in organizer's events.
     */
    private function organizerEventsQuery()
    {
        return Event::createdByOrganizer(Auth::id());
    }

    /**
     * Ensure the event belongs to the logged-in organizer.
     */
    private function authorizeOrganizerEvent(Event $event): void
    {
        if (! $event->isOwnedByOrganizer(Auth::id())) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Display a listing of events with filters.
     */
    public function index(Request $request)
    {
        $this->eventCompletionService->completePastEvents();

        $query = $this->organizerEventsQuery();

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

        $events = $query
            ->withSum('ticketCategories', 'no_of_tickets')
            ->withCount('ticketBookings')
            ->paginate(10)
            ->appends($request->all());

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
            'no_of_tickets' => 'required|integer|min:1',
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
            'no_of_tickets' => $validatedData['no_of_tickets'],
            'description' => $validatedData['description'],
            'contact_person' => $validatedData['contact_person'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id,
            'status' => Event::STATUS_UNPUBLISHED,
        ]);

        return redirect()->route('organizer.events.index')->with('success', 'Event created successfully. It is unpublished and hidden from attendees until you publish it.');
    }

    public function updateStatus(Request $request, Event $event)
    {
        $this->authorizeOrganizerEvent($event);

        $request->validate([
            'status' => 'required|in:unpublished,upcoming,ongoing,completed,cancelled',
        ]);

        if ($request->status === Event::STATUS_CANCELLED) {
            return back()->withErrors([
                'status' => 'Please use the cancel event option to provide a cancellation reason.',
            ]);
        }

        if ($request->status === Event::STATUS_UNPUBLISHED && $event->hasSoldTickets()) {
            return back()->withErrors([
                'status' => 'This event cannot be unpublished because at least one ticket has been sold.',
            ]);
        }

        if ($event->isCancelled()) {
            return back()->withErrors([
                'status' => 'Cancelled events cannot be changed to another status.',
            ]);
        }

        if ($event->isCompleted()) {
            return back()->withErrors([
                'status' => 'Completed events cannot be changed to another status.',
            ]);
        }

        if ($request->status === Event::STATUS_COMPLETED && ! $event->hasPassed()) {
            return back()->withErrors([
                'status' => 'Events can only be marked completed after the event date has passed.',
            ]);
        }

        $event->status = $request->status;
        $event->save();

        return back()->with('success', 'Event status updated successfully.');
    }

    public function cancel(Request $request, Event $event)
    {
        $this->authorizeOrganizerEvent($event);

        if ($event->isCancelled()) {
            return back()->withErrors([
                'status' => 'This event is already cancelled.',
            ]);
        }

        if ($event->isCompleted()) {
            return back()->withErrors([
                'status' => 'Completed events cannot be cancelled.',
            ]);
        }

        $validated = $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->eventCancellationService->cancel($event, $validated['cancellation_reason']);

        return back()->with('success', 'Event cancelled successfully. Attendees have been notified and refunds have been processed.');
    }

    /**
     * Show the form for editing an event.
     */
    public function edit(Event $event)
    {
        $this->authorizeOrganizerEvent($event);

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
        $this->authorizeOrganizerEvent($event);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'hosted_by' => 'required|exists:users,id',
            'category_id' => 'required|exists:event_categories,id',
            'date' => 'required|date',
            'time' => 'required',
            'place' => 'required|string|max:255',
            'no_of_tickets' => 'required|integer|min:1',
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
        $event->no_of_tickets = $validatedData['no_of_tickets'];
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
        $this->authorizeOrganizerEvent($event);

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
        $events = $this->organizerEventsQuery()->get();

        $csvData = [];
        $csvData[] = ['ID', 'Name', 'Place', 'Date', 'Time', 'tickets', 'Status', 'Created At'];

        foreach ($events as $event) {
            $csvData[] = [
                $event->id,
                $event->name,
                $event->place,
                $event->date,
                $event->time,
                $event->no_of_tickets,
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
        $events = $this->organizerEventsQuery()->get();
        $pdf = \PDF::loadView('organizer.exports.events_pdf', compact('events'));

        return $pdf->download('events_'.now()->format('Ymd_His').'.pdf');
    }

    public function show(Event $event)
    {
        $this->authorizeOrganizerEvent($event);
        $this->eventCompletionService->completeIfPast($event);
        $event->refresh();

        $event->loadCount(['likes', 'saves', 'comments', 'ratings', 'ticketBookings']);
        $event->load(['comments.user', 'ratings.user']);
        $event->loadAvg('ratings', 'score');
        $ticketCategories = $event->ticketCategories()->withCount('ticketBookings')->get();

        $postEventAnalytics = null;

        if ($event->isCompleted()) {
            $postEventAnalytics = [
                'revenue' => (float) $event->ticketBookings()
                    ->where('status', BookingStatusEnum::Confirmed)
                    ->sum('ticket_price'),
                'likes' => $event->likes_count ?? 0,
                'comments' => $event->comments_count ?? 0,
                'average_rating' => $event->ratings_avg_score,
                'ratings_count' => $event->ratings_count ?? 0,
                'ticket_sales' => $ticketCategories->map(function ($category) {
                    return [
                        'name' => $category->name,
                        'sold' => $category->ticket_bookings_count,
                        'revenue' => (float) $category->ticketBookings()
                            ->where('status', BookingStatusEnum::Confirmed)
                            ->sum('ticket_price'),
                    ];
                }),
            ];
        }

        return view('organizer.events.show', compact('event', 'ticketCategories', 'postEventAnalytics'));
    }

    public function showexportPdf(Event $event)
    {
        $this->authorizeOrganizerEvent($event);

        $ticketCategories = $event->ticketCategories;
        $pdf = Pdf::loadView('organizer.events.exports.event_pdf', compact('event', 'ticketCategories'));

        return $pdf->download($event->name.'_details.pdf');
    }

    public function showPublishedEvent(Event $event)
    {
        $this->eventCompletionService->completeIfPast($event);
        $event->refresh();

        $event->ensureVisibleToAttendees();

        $event->load(['host', 'eventCategory', 'contactPerson', 'ticketCategories', 'comments.user', 'ratings.user']);
        $event->loadCount(['likes', 'comments', 'ratings']);
        $event->loadAvg('ratings', 'score');

        $ticketCategories = $event->ticketCategories;
        $comments = $event->comments->sortByDesc('created_at');
        $ratings = $event->ratings->sortByDesc('created_at');
        $isLiked = Auth::user()->hasLiked($event);
        $likesCount = $event->likes_count;
        $isSaved = Auth::user()->hasSaved($event);
        $userRating = Auth::user()->ratingFor($event);
        $averageRating = $event->ratings_avg_score;
        $ratingsCount = $event->ratings_count;

        $eventCartItems = CartItem::query()
            ->where('user_id', Auth::id())
            ->where('event_id', $event->id)
            ->with('ticketCategory')
            ->latest()
            ->get();

        $eventCartTotal = $eventCartItems->sum(fn (CartItem $item) => $item->line_total);

        return view('attendee.show', compact(
            'event',
            'ticketCategories',
            'comments',
            'ratings',
            'isLiked',
            'likesCount',
            'isSaved',
            'userRating',
            'averageRating',
            'ratingsCount',
            'eventCartItems',
            'eventCartTotal'
        ));
    }
}
