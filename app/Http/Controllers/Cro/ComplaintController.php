<?php

namespace App\Http\Controllers\Cro;

use App\Enums\SupportTicketStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->get('status');

        $query = Complaint::query()
            ->with(['user', 'attachments', 'responses.user', 'assignee'])
            ->latest();

        if ($status && in_array($status, array_column(SupportTicketStatusEnum::cases(), 'value'))) {
            $query->where('status', $status);
        }

        $complaints = $query->get();

        $counts = [
            'open' => Complaint::where('status', SupportTicketStatusEnum::Open)->count(),
            'in_progress' => Complaint::where('status', SupportTicketStatusEnum::InProgress)->count(),
            'resolved' => Complaint::where('status', SupportTicketStatusEnum::Resolved)->count(),
            'closed' => Complaint::where('status', SupportTicketStatusEnum::Closed)->count(),
        ];

        return view('cro.complaints.index', compact('complaints', 'counts', 'status'));
    }

    public function reply(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        try {
            $this->complaintService->reply($complaint, Auth::user(), $validated['message']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }

        return back()->with('success', 'Reply sent to the attendee.');
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
        ]);

        $this->complaintService->updateStatus(
            $complaint,
            Auth::user(),
            SupportTicketStatusEnum::from($validated['status']),
        );

        return back()->with('success', 'Complaint status updated.');
    }

    public function downloadAttachment(Complaint $complaint, ComplaintAttachment $attachment): BinaryFileResponse
    {
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $path = public_path($attachment->file_path);

        abort_unless(file_exists($path), 404);

        return response()->download($path, $attachment->original_filename);
    }
}
