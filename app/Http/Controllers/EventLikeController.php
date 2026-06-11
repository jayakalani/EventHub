<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Like;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class EventLikeController extends Controller
{
    /**
     * Toggle like status for an event.
     */
    public function toggle(Event $event): RedirectResponse
    {
        $event->ensureInteractive();

        $user = Auth::user();

        $like = Like::query()
            ->where('user_id', $user->id)
            ->where('event_id', $event->id)
            ->first();

        if ($like) {
            $like->delete();

            return back()->with('success', 'Event unliked.');
        }

        Like::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        return back()->with('success', 'Event liked!');
    }
}
