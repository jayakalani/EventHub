<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Services\ComplaintService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComplaintController extends Controller
{
    public function __construct(
        protected ComplaintService $complaintService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $this->complaintService->submit(
            Auth::user(),
            $validated['subject'],
            $validated['message'],
            $request->file('attachments', []),
        );

        return back()->with('success', 'Your complaint has been submitted. You will receive a confirmation email shortly.');
    }

    public function downloadAttachment(Complaint $complaint, ComplaintAttachment $attachment): BinaryFileResponse
    {
        abort_unless($complaint->user_id === Auth::id(), 403);
        abort_unless($attachment->complaint_id === $complaint->id, 404);

        $path = public_path($attachment->file_path);

        abort_unless(file_exists($path), 404);

        return response()->download($path, $attachment->original_filename);
    }
}
