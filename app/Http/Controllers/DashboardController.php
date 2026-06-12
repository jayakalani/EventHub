<?php

namespace App\Http\Controllers;

use App\Enums\RefundRequestStatusEnum;
use App\Enums\SupportTicketStatusEnum;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Host;
use App\Models\Inquiry;
use App\Models\RefundRequest;
use App\Models\User;
use App\Models\UserRole;
use App\Services\EventCompletionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected EventCompletionService $eventCompletionService,
    ) {}

    /**
     * Redirect authenticated users to their role-specific dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        $roleName = $user->userRole?->name_en;

        return match ($roleName) {
            UserRole::ADMIN => $this->admin(),
            UserRole::ORGANIZER => $this->organizer(),
            UserRole::CRO => $this->cro(),
            UserRole::ATTENDEE => redirect()->route('attendee.dashboard'),
            default => redirect()->route('login')->with('error', 'Invalid role'),
        };
    }

    /**
     * Admin Dashboard
     */
    public function admin(): View
    {
        $totalUsers = User::count();
        $pendingVerifications = User::whereNull('email_verified_at')->count();
        $lockedAccounts = User::where('is_locked', 1)->count();
        $totalInquiries = Inquiry::count();
        $totalComplaints = Complaint::count();
        $pendingSupportCount = Inquiry::whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count()
            + Complaint::whereIn('status', [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress])->count();
        $resolvedSupportCount = Inquiry::where('status', SupportTicketStatusEnum::Resolved)->count()
            + Complaint::where('status', SupportTicketStatusEnum::Resolved)->count();

        return view('admin.dashboard', compact(
            'totalUsers',
            'pendingVerifications',
            'lockedAccounts',
            'totalInquiries',
            'totalComplaints',
            'pendingSupportCount',
            'resolvedSupportCount',
        ));
    }

    /**
     * Organizer Dashboard
     */
    public function organizer(): View
    {
        $organizerId = Auth::id();

        $totalEvents = Event::where('created_by', $organizerId)->count();
        $totalAttendees = User::whereHas('userRole', function ($query) {
            $query->where('name_en', UserRole::ATTENDEE);
        })->count();
        $upcomingEvents = Event::where('created_by', $organizerId)
            ->whereIn('status', [Event::STATUS_UPCOMING, Event::STATUS_ONGOING])
            ->count();

        $events = Event::where('created_by', $organizerId)
            ->latest()
            ->limit(5)
            ->get();

        return view('organizer.dashboard', compact(
            'totalEvents',
            'totalAttendees',
            'upcomingEvents',
            'events'
        ));
    }

    /**
     * CRO Dashboard
     */
    public function cro(): View
    {
        $pendingRefundCount = RefundRequest::query()
            ->where('status', RefundRequestStatusEnum::Pending)
            ->count();

        $processedRefundCount = RefundRequest::query()
            ->whereIn('status', [
                RefundRequestStatusEnum::Approved,
                RefundRequestStatusEnum::Declined,
            ])
            ->count();

        $openInquiryCount = Inquiry::where('status', SupportTicketStatusEnum::Open)->count();
        $openComplaintCount = Complaint::where('status', SupportTicketStatusEnum::Open)->count();
        $inProgressCount = Inquiry::where('status', SupportTicketStatusEnum::InProgress)->count()
            + Complaint::where('status', SupportTicketStatusEnum::InProgress)->count();

        return view('cro.dashboard', compact(
            'pendingRefundCount',
            'processedRefundCount',
            'openInquiryCount',
            'openComplaintCount',
            'inProgressCount',
        ));
    }

    /**
     * Public welcome page with browsable events.
     */
    public function welcome(Request $request): View
    {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $eventCategories = EventCategory::where('is_active', 1)->get();
        $selectedCategory = $request->category ?? null;

        return view('welcome', compact(
            'events',
            'eventCategories',
            'selectedCategory'
        ));
    }

    /**
     * Attendee Dashboard
     */
    public function attendee(Request $request): View
    {
        $this->eventCompletionService->completePastEvents();

        $events = $this->withUserEventFlags($this->filteredEventsQuery($request))->get();
        $pastEvents = $this->withUserEventFlags($this->pastEventsQuery($request))->get();

        $eventCategories = EventCategory::where('is_active', 1)->get();

        $selectedCategory = $request->category ?? null;

        $selectedHost = $request->filled('host')
            ? Host::query()->where('is_active', true)->find($request->host)
            : null;

        return view('attendee.dashboard', compact(
            'events',
            'pastEvents',
            'eventCategories',
            'selectedCategory',
            'selectedHost'
        ));
    }

    private function pastEventsQuery(Request $request)
    {
        $query = Event::query()->pastForAttendees()->latest('date');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('host')) {
            $query->where('hosted_by', $request->host);
        }

        return $query->withCount('likes');
    }

    private function filteredEventsQuery(Request $request)
    {
        $query = Event::query()->activeForAttendees();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('host')) {
            $query->where('hosted_by', $request->host);
        }

        return $query->withCount('likes');
    }

    private function withUserEventFlags($query)
    {
        if (! Auth::check()) {
            return $query;
        }

        return $query
            ->withExists(['likes as is_liked' => function ($likeQuery) {
                $likeQuery->where('user_id', Auth::id());
            }])
            ->withExists(['saves as is_saved' => function ($saveQuery) {
                $saveQuery->where('user_id', Auth::id());
            }]);
    }
}
