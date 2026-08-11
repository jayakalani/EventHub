<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\CartItem;
use App\Models\Event;
use App\Enums\BookingStatusEnum;
use App\Models\EventCategory;
use App\Models\EventView;
use App\Models\Host;
use App\Models\User;
use App\Models\UserRole;
use App\Services\AdminNotificationService;
use App\Services\CroNotificationService;
use App\Services\CartInventoryService;
use App\Services\EventCancellationService;
use App\Services\EventNotificationService;
use App\Services\EventPostponementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class EventController extends Controller
{
    public function __construct(
        protected CartInventoryService $cartInventoryService,
        protected EventCancellationService $eventCancellationService,
        protected EventNotificationService $eventNotificationService,
        protected EventPostponementService $eventPostponementService,
        protected CroNotificationService $croNotificationService,
        protected AdminNotificationService $adminNotificationService,
    ) {}

    /**
     * Base query scoped to the logged-in organizer's events.
     */
    private function organizerEventsQuery()
    {
        return Event::createdByOrganizer(Auth::id());
    }

    /**
     * Display a listing of events with filters.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Event::class);

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
                    ->orWhereHas('artists', function ($artistQuery) use ($search) {
                        $artistQuery->where('name', 'like', "%{$search}%");
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
            ->with(['host', 'artists', 'eventCategory'])
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
        $this->authorize('create', Event::class);

        $hosts = Host::query()->where('is_active', true)->orderBy('name')->get();
        $artists = Artist::query()->where('is_active', true)->orderBy('name')->get();
        $event_categories = EventCategory::all();

        $croUsers = User::whereHas('userRole', function ($q) {
            $q->where('name_en', UserRole::CRO);
        })->get();

        return view('organizer.events.create', compact('hosts', 'artists', 'event_categories', 'croUsers'));
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Event::class);

        $scheduleTba = $request->boolean('schedule_tba');
        $category = EventCategory::find($request->input('category_id'));
        $allowsArtists = $category?->allowsArtists() ?? false;

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'host_id' => 'required|exists:hosts,id',
            'category_id' => 'required|exists:event_categories,id',
            'artist_ids' => [$allowsArtists ? 'nullable' : 'prohibited', 'array'],
            'artist_ids.*' => 'integer|exists:artists,id',
            'schedule_tba' => 'sometimes|boolean',
            'date' => ($scheduleTba ? 'nullable' : 'required').'|date',
            'time' => ($scheduleTba ? 'nullable' : 'required'),
            'place' => ($scheduleTba ? 'nullable' : 'required').'|string|max:255',
            'no_of_tickets' => 'required|integer|min:1',
            'description' => 'required|string',
            'contact_person' => 'required|exists:users,id',
            'cover' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'refunds_allowed' => 'sometimes|boolean',
            'refund_full_days_before_close' => 'required_if:refunds_allowed,1|nullable|integer|min:0|max:365',
            'refund_full_percentage' => 'required_if:refunds_allowed,1|nullable|integer|min:0|max:100',
            'refund_partial_percentage' => 'required_if:refunds_allowed,1|nullable|integer|min:0|max:100',
        ]);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/events/', $fileName);
        }

        $refundsAllowed = $request->boolean('refunds_allowed');

        $event = Event::create([
            'name' => $validatedData['name'],
            'host_id' => $validatedData['host_id'],
            'category_id' => $validatedData['category_id'],
            'date' => $scheduleTba ? null : $validatedData['date'],
            'time' => $scheduleTba ? null : $validatedData['time'],
            'place' => $scheduleTba ? null : $validatedData['place'],
            'date_tba' => $scheduleTba,
            'no_of_tickets' => $validatedData['no_of_tickets'],
            'description' => $validatedData['description'],
            'contact_person' => $validatedData['contact_person'],
            'cover' => $fileName,
            'created_by' => Auth::user()->id,
            'status' => Event::STATUS_UNPUBLISHED,
            'refunds_allowed' => $refundsAllowed,
            'refund_full_days_before_close' => $refundsAllowed
                ? (int) $validatedData['refund_full_days_before_close']
                : 7,
            'refund_full_percentage' => $refundsAllowed
                ? (int) $validatedData['refund_full_percentage']
                : 100,
            'refund_partial_percentage' => $refundsAllowed
                ? (int) $validatedData['refund_partial_percentage']
                : 75,
        ]);

        $event->artists()->sync($allowsArtists ? ($validatedData['artist_ids'] ?? []) : []);

        $this->croNotificationService->notifyEventAssigned($event);

        return redirect()->route('organizer.events.index')->with('success', 'Event created successfully. It is unpublished and hidden from attendees until you publish it.');
    }

    public function updateStatus(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $request->validate([
            'status' => 'required|in:unpublished,upcoming,ongoing,completed,cancelled,postponed',
        ]);

        if ($request->status === Event::STATUS_CANCELLED) {
            return back()->withErrors([
                'status' => 'Please use the cancel event option to provide a cancellation reason.',
            ]);
        }

        if ($request->status === Event::STATUS_POSTPONED) {
            return back()->withErrors([
                'status' => 'Please use the postpone event option to provide postponement details.',
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

        if ($event->isPostponed()) {
            if ($request->status !== Event::STATUS_ONGOING) {
                return back()->withErrors([
                    'status' => 'Postponed events can only be changed to Ongoing (or cancelled). Status cannot be set to Upcoming.',
                ]);
            }

            if ($event->hasDateYetToBeScheduled()) {
                return back()->withErrors([
                    'status' => 'Set a place/date/time for this postponed event before marking it Ongoing.',
                ]);
            }
        }

        if ($event->isOngoing()) {
            if ($request->status !== Event::STATUS_COMPLETED) {
                return back()->withErrors([
                    'status' => 'Ongoing events can only be changed to Completed.',
                ]);
            }

            if (! $event->hasPassed()) {
                return back()->withErrors([
                    'status' => 'Events can only be marked completed after the event date has passed.',
                ]);
            }
        }

        if ($request->status === Event::STATUS_COMPLETED && ! $event->isOngoing()) {
            return back()->withErrors([
                'status' => 'Only ongoing events can be marked as completed.',
            ]);
        }

        $wasUnpublished = $event->status === Event::STATUS_UNPUBLISHED;
        $newStatus = $request->status;

        $event->status = $newStatus;
        $event->save();

        if ($wasUnpublished && in_array($newStatus, [Event::STATUS_UPCOMING, Event::STATUS_ONGOING], true)) {
            $this->eventNotificationService->notifyNewEventPublished($event);
        }

        return back()->with('success', 'Event status updated successfully.');
    }

    public function postpone(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        if (! $event->canBePostponed()) {
            return back()->withErrors([
                'status' => 'Only upcoming events can be postponed. Cancelled, completed, and ongoing events cannot be postponed.',
            ]);
        }

        $validated = $request->validate([
            'postponement_reason' => ['required', 'string', 'min:10', 'max:2000'],
            'new_date' => ['nullable', 'date', 'after_or_equal:today'],
            'new_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'notify_email' => ['sometimes', 'boolean'],
            'notify_in_app' => ['sometimes', 'boolean'],
        ]);

        try {
            $this->eventPostponementService->postpone(
                $event,
                $validated['postponement_reason'],
                $validated['new_date'] ?? null,
                ! empty($validated['new_date']) ? ($validated['new_time'] ?? null) : null,
                $request->boolean('notify_email'),
                $request->boolean('notify_in_app'),
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['status' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Event postponed successfully. Confirmed ticket holders have been notified as selected.');
    }

    public function updatePostponedSchedule(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'schedule_date' => ['required', 'date', 'after_or_equal:today'],
            'schedule_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'schedule_place' => ['required', 'string', 'max:255'],
            'notify_attendees' => ['sometimes', 'boolean'],
        ]);

        try {
            if ($event->isPostponed()) {
                $this->eventPostponementService->setPostponedSchedule(
                    $event,
                    $validated['schedule_date'],
                    $validated['schedule_time'] ?? null,
                    $validated['schedule_place'],
                    $request->boolean('notify_attendees', true),
                );

                return back()->with('success', 'Postponed event schedule updated. Status remains Postponed.');
            }

            if ($event->isUpcomingScheduleTba() || ($event->status === Event::STATUS_UPCOMING && $event->date_tba)) {
                $this->eventPostponementService->confirmUpcomingSchedule(
                    $event,
                    $validated['schedule_date'],
                    $validated['schedule_time'] ?? null,
                    $validated['schedule_place'],
                    $request->boolean('notify_attendees', true),
                );

                return back()->with('success', 'Upcoming event schedule confirmed. Place, date and time are now set.');
            }
        } catch (RuntimeException $e) {
            return back()->withErrors(['schedule_date' => $e->getMessage()])->withInput();
        }

        return back()->withErrors([
            'schedule_date' => 'Only postponed events or upcoming events without a confirmed schedule can use this action.',
        ]);
    }

    public function cancel(Request $request, Event $event)
    {
        $this->authorize('update', $event);

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
        $this->authorize('update', $event);

        $hosts = Host::query()->where('is_active', true)->orderBy('name')->get();
        $artists = Artist::query()->where('is_active', true)->orderBy('name')->get();
        $event_categories = EventCategory::all();
        $croUsers = User::whereHas('userRole', function ($q) {
            $q->where('name_en', UserRole::CRO);
        })->get();

        $event->load('artists');

        return view('organizer.events.edit', compact('event', 'hosts', 'artists', 'event_categories', 'croUsers'));
    }

    /**
     * Update an event.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $policyLocked = $event->hasSoldTickets();
        $scheduleTba = $request->boolean('schedule_tba');
        $category = EventCategory::find($request->input('category_id'));
        $allowsArtists = $category?->allowsArtists() ?? false;

        $rules = [
            'name' => 'required|string|max:255',
            'host_id' => 'required|exists:hosts,id',
            'category_id' => 'required|exists:event_categories,id',
            'artist_ids' => [$allowsArtists ? 'nullable' : 'prohibited', 'array'],
            'artist_ids.*' => 'integer|exists:artists,id',
            'schedule_tba' => 'sometimes|boolean',
            'date' => ($scheduleTba ? 'nullable' : 'required').'|date',
            'time' => ($scheduleTba ? 'nullable' : 'required'),
            'place' => ($scheduleTba ? 'nullable' : 'required').'|string|max:255',
            'no_of_tickets' => 'required|integer|min:1',
            'description' => 'required|string',
            'contact_person' => 'required|exists:users,id',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        if (! $policyLocked) {
            $rules['refunds_allowed'] = 'sometimes|boolean';
            $rules['refund_full_days_before_close'] = 'required_if:refunds_allowed,1|nullable|integer|min:0|max:365';
            $rules['refund_full_percentage'] = 'required_if:refunds_allowed,1|nullable|integer|min:0|max:100';
            $rules['refund_partial_percentage'] = 'required_if:refunds_allowed,1|nullable|integer|min:0|max:100';
        }

        $validatedData = $request->validate($rules);

        if ($request->hasfile('cover')) {
            $file = $request->file('cover');
            $extension = $file->getClientOriginalExtension();
            $fileName = time().'.'.$extension;
            $file->move('uploads/covers/events/', $fileName);
        }

        $original = $event->only(EventNotificationService::UPDATABLE_FIELDS);
        $wasPostponed = $event->isPostponed();
        $originalDate = (string) $event->date;
        $originalTime = (string) $event->time;
        $previousContactPersonId = (int) $event->contact_person;
        $originalPaymentSettings = [
            'refunds_allowed' => (bool) $event->refunds_allowed,
            'refund_full_days_before_close' => (int) $event->refund_full_days_before_close,
            'refund_full_percentage' => (int) $event->refund_full_percentage,
            'refund_partial_percentage' => (int) $event->refund_partial_percentage,
        ];

        $event->name = $validatedData['name'];
        $event->host_id = $validatedData['host_id'];
        $event->category_id = $validatedData['category_id'];

        if ($scheduleTba && ! $wasPostponed) {
            $event->date = null;
            $event->time = null;
            $event->place = null;
            $event->date_tba = true;
        } else {
            $event->date = $validatedData['date'];
            $event->time = $validatedData['time'];
            $event->place = $validatedData['place'];
            if (! $wasPostponed) {
                $event->date_tba = false;
            }
        }
        $event->no_of_tickets = $validatedData['no_of_tickets'];
        $event->description = $validatedData['description'];
        $event->contact_person = $validatedData['contact_person'];
        $event->cover = $fileName ?? $event->cover;

        $paymentSettingsChanged = false;

        if (! $policyLocked) {
            $refundsAllowed = $request->boolean('refunds_allowed');
            $event->refunds_allowed = $refundsAllowed;
            $event->refund_full_days_before_close = $refundsAllowed
                ? (int) $validatedData['refund_full_days_before_close']
                : $event->refund_full_days_before_close;
            $event->refund_full_percentage = $refundsAllowed
                ? (int) $validatedData['refund_full_percentage']
                : $event->refund_full_percentage;
            $event->refund_partial_percentage = $refundsAllowed
                ? (int) $validatedData['refund_partial_percentage']
                : $event->refund_partial_percentage;

            $paymentSettingsChanged =
                $originalPaymentSettings['refunds_allowed'] !== (bool) $event->refunds_allowed
                || $originalPaymentSettings['refund_full_days_before_close'] !== (int) $event->refund_full_days_before_close
                || $originalPaymentSettings['refund_full_percentage'] !== (int) $event->refund_full_percentage
                || $originalPaymentSettings['refund_partial_percentage'] !== (int) $event->refund_partial_percentage;
        }

        $event->save();

        $event->artists()->sync($allowsArtists ? ($validatedData['artist_ids'] ?? []) : []);

        if ($paymentSettingsChanged) {
            $this->adminNotificationService->notifyPaymentSettingsChanged($event->fresh(), Auth::user());
        }

        if ((int) $validatedData['contact_person'] !== $previousContactPersonId) {
            $this->croNotificationService->notifyEventAssigned($event->fresh());
        }

        $dateChanged = ! $scheduleTba && (
            (string) ($validatedData['date'] ?? '') !== $originalDate
            || (string) ($validatedData['time'] ?? '') !== $originalTime
            || (string) ($validatedData['place'] ?? '') !== (string) ($original['place'] ?? '')
        );

        if ($wasPostponed && $dateChanged) {
            $this->eventPostponementService->setPostponedSchedule(
                $event->fresh(),
                $validatedData['date'],
                $validatedData['time'],
                $validatedData['place'],
                notify: true,
            );
        } else {
            $changes = EventNotificationService::buildChangesFromEvent($event, $original);

            if ($changes !== []) {
                $this->eventNotificationService->notifyEventUpdated($event, $changes);
            }
        }

        return redirect()->route('organizer.events.index')->with('success', 'Event updated successfully.');
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        if ($event->hasSoldTickets()) {
            return back()->with('error', 'This event cannot be deleted because at least one ticket has been sold.');
        }

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
        $this->authorize('viewAny', Event::class);

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
        $this->authorize('viewAny', Event::class);

        $events = $this->organizerEventsQuery()->with(['host', 'artists', 'eventCategory'])->get();
        $pdf = \PDF::loadView('organizer.exports.events_pdf', compact('events'));

        return $pdf->download('events_'.now()->format('Ymd_His').'.pdf');
    }

    public function show(Event $event)
    {
        $this->authorize('view', $event);

        $event->loadCount(['likes', 'saves', 'comments', 'ratings', 'ticketBookings']);
        $event->load(['host', 'artists', 'comments.user', 'ratings.user']);
        $event->loadAvg('ratings', 'score');
        $ticketCategories = $event->ticketCategories()
            ->withCount([
                'ticketBookings',
                'ticketBookings as confirmed_bookings_count' => function ($query) {
                    $query->where('status', BookingStatusEnum::Confirmed);
                },
            ])
            ->get();

        $holdSummary = $this->cartInventoryService->holdSummaryByCategoryIds(
            $ticketCategories->pluck('id')->all()
        );

        $ticketCategories->each(function ($category) use ($holdSummary) {
            $summary = $holdSummary[$category->id] ?? ['held' => 0, 'abandoned' => 0];
            $category->held_quantity = $summary['held'];
            $category->abandoned_quantity = $summary['abandoned'];
        });

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
                        'sold' => $category->confirmed_bookings_count,
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
        $this->authorize('view', $event);

        $event->load(['host', 'artists', 'eventCategory', 'contactPerson']);
        $ticketCategories = $event->ticketCategories;
        $pdf = Pdf::loadView('organizer.exports.show_pdf', compact('event', 'ticketCategories'));

        return $pdf->download($event->name.'_details.pdf');
    }

    public function showPublishedEvent(Event $event)
    {
        $event->ensureVisibleToAttendees();

        EventView::query()->create([
            'event_id' => $event->id,
            'user_id' => Auth::id(),
            'session_id' => substr((string) request()->session()->getId(), 0, 64),
        ]);

        $event->load(['host', 'artists', 'eventCategory', 'contactPerson', 'ticketCategories', 'comments.user', 'ratings.user']);
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
