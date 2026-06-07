<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\SavedEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EventSaveController extends Controller
{
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

        SavedEvent::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        return back()->with('success', 'Event saved!');
    }
}
