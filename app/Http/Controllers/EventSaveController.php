<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SavedEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EventSaveController extends Controller
{
    /**
     * List events the attendee has saved/bookmarked.
     */
    public function index(): View
    {
        $events = Auth::user()
            ->savedEvents()
            ->with(['host', 'eventCategory'])
            ->withCount('likes')
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', Auth::id()),
                'saves as is_saved' => fn ($query) => $query->where('user_id', Auth::id()),
            ])
            ->orderByPivot('created_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return view('attendee.saved.index', compact('events'));
    }

    /**
     * Toggle saved status for an event.
     */
    public function toggle(Event $event): RedirectResponse
    {
        $user = Auth::user();

        $savedEvent = SavedEvent::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($savedEvent) {
            $savedEvent->delete();

            return back()->with('success', 'Event unsaved.');
        }

        $event->ensureInteractive();

        SavedEvent::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        return back()->with('success', 'Event saved!');
    }
}
