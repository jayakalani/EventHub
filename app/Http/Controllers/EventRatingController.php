<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Rating;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventRatingController extends Controller
{
    /**
     * Store or update a rating for an event.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $event->ensureFeedbackAllowed();

        $validated = $request->validate([
            'score' => 'required|integer|min:1|max:5',
        ]);

        Rating::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'event_id' => $event->id,
            ],
            [
                'score' => $validated['score'],
            ]
        );

        return back()->with('success', 'Rating submitted.');
    }

    /**
     * Remove the authenticated user's rating.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $event->ensureFeedbackAllowed();

        Rating::query()
            ->where('user_id', Auth::id())
            ->where('event_id', $event->id)
            ->delete();

        return back()->with('success', 'Rating removed.');
    }
}
