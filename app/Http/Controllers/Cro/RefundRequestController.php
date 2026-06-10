<?php

namespace App\Http\Controllers\Cro;

use App\Enums\RefundRequestStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\RefundRequest;
use App\Services\RefundRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;

class RefundRequestController extends Controller
{
    public function __construct(
        protected RefundRequestService $refundRequestService,
    ) {}

    public function index(): View
    {
        $pendingRequests = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->with(['user', 'ticketBooking.event', 'ticketBooking.ticketCategory', 'ticketBooking.payment'])
            ->latest()
            ->get();

        $processedCount = RefundRequest::query()
            ->whereIn('status', [
                RefundRequestStatusEnum::Approved,
                RefundRequestStatusEnum::Declined,
            ])
            ->count();

        return view('cro.refund-requests.index', compact('pendingRequests', 'processedCount'));
    }

    public function approve(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $validated = $request->validate([
            'cro_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->refundRequestService->approve($refundRequest, Auth::user(), $validated['cro_notes'] ?? null);
        } catch (RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return back()->with('success', 'Refund approved and credited to the attendee wallet.');
    }

    public function decline(Request $request, RefundRequest $refundRequest): RedirectResponse
    {
        $validated = $request->validate([
            'cro_notes' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'cro_notes.required' => 'A reason is required when declining a refund request.',
            'cro_notes.min' => 'Please provide at least 10 characters explaining why the refund was declined.',
        ]);

        try {
            $this->refundRequestService->decline($refundRequest, Auth::user(), $validated['cro_notes']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['refund' => $e->getMessage()]);
        }

        return back()->with('success', 'Refund request declined.');
    }
}
