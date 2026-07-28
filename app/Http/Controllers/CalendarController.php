<?php

namespace App\Http\Controllers;

use App\Services\AttendeeCalendarService;
use App\Services\OrganizerCalendarService;
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

    public function organizer(OrganizerCalendarService $calendarService): View
    {
        $organizerId = (int) Auth::id();

        $calendarEvents = $calendarService->formatForCalendar($organizerId);
        $upcomingEvents = $calendarService->getUpcomingEvents($organizerId);
        $pastEvents = $calendarService->getPastEvents($organizerId);
        $draftEvents = $calendarService->getDraftEvents($organizerId);
        $statusColors = OrganizerCalendarService::statusColors();

        return view('organizer.calendar.index', compact(
            'calendarEvents',
            'upcomingEvents',
            'pastEvents',
            'draftEvents',
            'statusColors',
        ));
    }
}
