<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\InquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService,
    ) {}

    public function store(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $this->inquiryService->submit(
            Auth::user(),
            $event,
            $validated['subject'],
            $validated['message'],
        );

        return back()->with('success', 'Your inquiry has been submitted. You will receive a confirmation email shortly.');
    }
}
