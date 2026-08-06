<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Payment;
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
            ->createdByOrganizer($organizerId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('organizer.sales.index', compact('sales', 'stats', 'events', 'filters'));
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
            'Purchased At',
            'Buyer Name',
            'Buyer Email',
            'Event',
            'Ticket Types',
            'Quantity',
            'Amount (LKR)',
            'Payment Reference',
            'Payment Method',
        ];

        foreach ($sales as $purchase) {
            $csvData[] = [
                $purchase['booked_at_formatted'] ?? '',
                $purchase['buyer'] ?? '',
                $purchase['email'] ?? '',
                $purchase['event'] ?? '',
                implode('; ', $purchase['categories'] ?? []),
                (string) ($purchase['quantity'] ?? 0),
                number_format((float) ($purchase['amount'] ?? 0), 2, '.', ''),
                $purchase['payment_reference'] ?? '',
                $purchase['payment_method'] ?? '',
            ];
        }

        $csvData[] = [];
        $csvData[] = [
            'Summary',
            '',
            '',
            '',
            '',
            (string) $stats['tickets'],
            number_format((float) $stats['revenue'], 2, '.', ''),
            'Purchases: '.$stats['purchases'],
            'Buyers: '.$stats['unique_buyers'],
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
     * @return array{search?: string|null, event_id?: int|null, from_date?: string|null, to_date?: string|null}
     */
    private function validatedFilters(Request $request): array
    {
        $request->merge([
            'search' => $request->filled('search') ? $request->input('search') : null,
            'event_id' => $request->filled('event_id') ? $request->input('event_id') : null,
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
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
    }
}
