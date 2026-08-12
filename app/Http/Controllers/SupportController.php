<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Inquiry;
use App\Models\ticketBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function index(Request $request): View
    {
        $userId = Auth::id();

        $inquiries = Inquiry::query()
            ->where('user_id', $userId)
            ->with(['event', 'responses'])
            ->latest()
            ->get();

        $complaints = Complaint::query()
            ->where('user_id', $userId)
            ->with(['event', 'attachments', 'responses'])
            ->latest()
            ->get();

        $complaintEvents = Event::query()
            ->whereIn('id', ticketBooking::query()
                ->where('user_id', $userId)
                ->select('event_id'))
            ->orderByDesc('date')
            ->get(['id', 'name', 'date']);

        $tab = $request->get('tab', 'inquiries');

        return view('attendee.support.index', compact(
            'inquiries',
            'complaints',
            'complaintEvents',
            'tab',
        ));
    }
}
