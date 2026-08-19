<?php

namespace App\Http\Controllers\Cro;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Event::class);

        $query = $this->filteredEventsQuery($request);
        $croId = (int) Auth::id();

        $stats = [
            'matched' => (clone $query)->count(),
            'upcoming' => (clone $query)->where('status', Event::STATUS_UPCOMING)->count(),
            'ongoing' => (clone $query)->where('status', Event::STATUS_ONGOING)->count(),
            'postponed' => (clone $query)->where('status', Event::STATUS_POSTPONED)->count(),
        ];

        $events = $query
            ->with(['organizer', 'eventCategory', 'host'])
            ->withSum('ticketCategories', 'no_of_tickets')
            ->latest()
            ->paginate(12)
            ->appends($request->query());

        $organizerIds = Event::query()
            ->assignedToCro($croId)
            ->select('created_by');

        $organizers = User::query()
            ->whereIn('id', $organizerIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        $categories = EventCategory::query()->orderBy('name')->get(['id', 'name']);
        $hasActiveFilters = $this->hasActiveFilters($request);

        return view('cro.events.index', compact(
            'events',
            'stats',
            'organizers',
            'categories',
            'hasActiveFilters',
        ));
    }

    public function show(Event $event): View
    {
        $event->load([
            'organizer.userRole',
            'eventCategory',
            'host',
            'artists',
            'contactPerson',
            'ticketCategories',
        ]);

        $this->authorize('view', $event);

        return view('cro.events.show', compact('event'));
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $events = $this->filteredEventsQuery($request)
            ->with(['organizer', 'eventCategory', 'host'])
            ->withSum('ticketCategories', 'no_of_tickets')
            ->latest()
            ->get();

        $csvData = [
            ['ID', 'Name', 'Organizer', 'Category', 'Status', 'Date', 'Place', 'Tickets', 'Host'],
        ];

        foreach ($events as $event) {
            $csvData[] = [
                $event->id,
                $event->name,
                $event->organizer?->full_name ?? '—',
                $event->eventCategory?->name ?? '—',
                $event->trashed() ? 'archived' : $event->status,
                $event->formattedScheduleDate() ?: 'TBA',
                $event->displayPlace(),
                (int) ($event->ticket_categories_sum_no_of_tickets ?: $event->no_of_tickets),
                $event->host?->name ?? '—',
            ];
        }

        $filename = 'cro_events_'.now()->format('Ymd_His').'.csv';
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function exportPdf(Request $request)
    {
        $this->authorize('viewAny', Event::class);

        $events = $this->filteredEventsQuery($request)
            ->with(['organizer', 'eventCategory', 'host'])
            ->withSum('ticketCategories', 'no_of_tickets')
            ->latest()
            ->get();

        $pdf = Pdf::loadView('cro.exports.events_pdf', compact('events'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('cro_events_'.now()->format('Ymd_His').'.pdf');
    }

    private function filteredEventsQuery(Request $request): Builder
    {
        $query = Event::query()->assignedToCro((int) Auth::id());

        if ($request->string('status')->toString() === 'archived') {
            $query->onlyTrashed();
        } else {
            $query->withoutTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function (Builder $nested) use ($search) {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('place', 'like', "%{$search}%")
                    ->orWhereHas('organizer', function (Builder $organizerQuery) use ($search) {
                        $organizerQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('eventCategory', function (Builder $categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('host', function (Builder $hostQuery) use ($search) {
                        $hostQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('organizer')) {
            $query->where('created_by', (int) $request->input('organizer'));
        }

        if ($request->filled('category')) {
            $query->where('category_id', (int) $request->input('category'));
        }

        if ($request->filled('status') && $request->input('status') !== 'archived') {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->input('to_date'));
        }

        return $query;
    }

    private function hasActiveFilters(Request $request): bool
    {
        return $request->filled('search')
            || $request->filled('organizer')
            || $request->filled('category')
            || $request->filled('status')
            || $request->filled('from_date')
            || $request->filled('to_date');
    }
}
