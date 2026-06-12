<?php

namespace App\Http\Controllers\Cro;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\Inquiry;
use App\Services\ComplaintService;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->get('status');

        $query = Inquiry::query()
            ->with(['user', 'event', 'responses.user', 'assignee'])
            ->latest();

        if ($status && in_array($status, array_column(SupportTicketStatusEnum::cases(), 'value'))) {
            $query->where('status', $status);
        }

        $inquiries = $query->get();

        $counts = [
            'open' => Inquiry::where('status', SupportTicketStatusEnum::Open)->count(),
            'in_progress' => Inquiry::where('status', SupportTicketStatusEnum::InProgress)->count(),
            'resolved' => Inquiry::where('status', SupportTicketStatusEnum::Resolved)->count(),
            'closed' => Inquiry::where('status', SupportTicketStatusEnum::Closed)->count(),
        ];

        return view('cro.inquiries.index', compact('inquiries', 'counts', 'status'));
    }

    public function reply(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $this->inquiryService->reply($inquiry, Auth::user(), $validated['message']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', 'Reply sent to the attendee.');
    }

    public function updateStatus(Request $request, Inquiry $inquiry): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $this->inquiryService->updateStatus(
            $inquiry,
            Auth::user(),
            SupportTicketStatusEnum::from($validated['status']),
        );

        return back()->with('success', 'Inquiry status updated.');
    }
}
