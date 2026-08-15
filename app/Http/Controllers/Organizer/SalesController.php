<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\BookingStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
use App\Models\ticketCategory;
use App\Services\OrganizerSalesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SalesController extends Controller
{
    public function __construct(
        protected OrganizerSalesService $salesService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $sales = $this->salesService->paginate($organizerId, $filters, 20);
        $stats = $this->salesService->stats($organizerId, $filters);

        $events = Event::query()
            ->forFilter()
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name', 'deleted_at']);

        $ticketCategories = $this->ticketCategoryOptions($organizerId, $filters['event_id'] ?? null);
        $statuses = BookingStatusEnum::salesListStatuses();

        return view('organizer.sales.index', compact(
            'sales',
            'stats',
            'events',
            'ticketCategories',
            'statuses',
            'filters',
        ));
    }

    public function exportCsv(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $sales = $this->salesService->all($organizerId, $filters);
        $stats = $this->salesService->stats($organizerId, $filters);

        $csvData = [];
        $csvData[] = [
            'Ticket Number',
            'Event',
            'Ticket Category',
            'Amount (LKR)',
            'Original Amount (LKR)',
            'Refund Amount (LKR)',
            'Purchased At',
            'Check-in Status',
            'Ticket Status',
        ];

        foreach ($sales as $ticket) {
            $csvData[] = [
                $ticket['ticket_number'] ?? '',
                $ticket['event'] ?? '',
                $ticket['category'] ?? '',
                number_format((float) ($ticket['amount'] ?? 0), 2, '.', ''),
                number_format((float) ($ticket['original_amount'] ?? $ticket['amount'] ?? 0), 2, '.', ''),
                number_format((float) ($ticket['refund_amount'] ?? 0), 2, '.', ''),
                $ticket['booked_at_formatted'] ?? '',
                $ticket['check_in_status'] ?? '',
                $ticket['status'] ?? '',
            ];
        }

        $csvData[] = [];
        $csvData[] = [
            'Summary',
            'Tickets: '.$stats['tickets'],
            'Purchases: '.$stats['purchases'],
            number_format((float) $stats['revenue'], 2, '.', ''),
            '',
            '',
            'Buyers: '.$stats['unique_buyers'],
            '',
            '',
        ];

        $filename = 'sales_'.now()->format('Ymd_His').'.csv';
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

    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', Payment::class);

        $filters = $this->validatedFilters($request);
        $organizerId = (int) Auth::id();

        $sales = $this->salesService->all($organizerId, $filters);
        $stats = $this->salesService->stats($organizerId, $filters);

        $eventName = null;
        if (! empty($filters['event_id'])) {
            $eventName = Event::query()
                ->forFilter()
                ->createdByOrganizer($organizerId)
                ->whereKey($filters['event_id'])
                ->value('name');
        }

        $pdf = Pdf::loadView('organizer.exports.sales_pdf', [
            'sales' => $sales,
            'stats' => $stats,
            'filters' => $filters,
            'eventName' => $eventName,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('sales_'.now()->format('Ymd_His').'.pdf');
    }

    /**
     * @return array{
     *     search?: string|null,
     *     event_id?: int|null,
     *     ticket_category?: string|null,
     *     status?: string|null,
     *     from_date?: string|null,
     *     to_date?: string|null
     * }
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'search' => $request->filled('search') ? $request->input('search') : null,
            'event_id' => $request->filled('event_id') ? $request->input('event_id') : null,
            'ticket_category' => $request->filled('ticket_category') ? $request->input('ticket_category') : null,
            'status' => $request->filled('status') ? $request->input('status') : null,
            'from_date' => $request->filled('from_date') ? $request->input('from_date') : null,
            'to_date' => $request->filled('to_date') ? $request->input('to_date') : null,
        ]);

        return $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where(fn ($query) => $query->where('created_by', Auth::id())),
            ],
            'ticket_category' => [
                'nullable',
                'string',
                'max:255',
                Rule::exists('ticket_categories', 'name')->where(function ($query) use ($request) {
                    $query->whereIn(
                        'event_id',
                        Event::query()->forFilter()->createdByOrganizer((int) Auth::id())->select('id')
                    );

                    if ($request->filled('event_id')) {
                        $query->where('event_id', $request->input('event_id'));
                    }
                }),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_column(BookingStatusEnum::salesListStatuses(), 'value')),
            ],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }

    /**
     * @return list<string>
     */
    private function ticketCategoryOptions(int $organizerId, ?int $eventId = null): array
    {
        return ticketCategory::query()
            ->whereHas('event', fn ($query) => $query->withTrashed()->createdByOrganizer($organizerId))
            ->when($eventId, fn ($query) => $query->where('event_id', $eventId))
            ->orderBy('name')
            ->distinct()
            ->pluck('name')
            ->filter()
            ->values()
            ->all();
    }
}
