<?php

namespace App\Http\Controllers;

use App\Services\AttendeeCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(AttendeeCalendarService $calendarService): View
    {
        $userId = (int) Auth::id();

        $calendarEvents = $calendarService->formatForCalendar($userId);
        $upcomingEvents = $calendarService->getUpcomingBookedEvents($userId);
        $pastEvents = $calendarService->getPastBookedEvents($userId);
        $statusColors = AttendeeCalendarService::statusColors();

        return view('attendee.calendar.index', compact(
            'calendarEvents',
            'upcomingEvents',
            'pastEvents',
            'statusColors',
        ));
    }
}
