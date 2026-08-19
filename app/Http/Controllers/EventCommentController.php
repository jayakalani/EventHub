<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EventCommentController extends Controller
{
    /**
     * Store a new comment on an event.
     */
    public function store(Request $request, Event $event): RedirectResponse
    {
        $event->ensurePurchaserCanLeaveFeedback(Auth::user());

        $validated = $request->validate([
            'body' => 'required|string|min:1|max:1000',
        ]);

        Comment::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Review added.');
    }

    /**
     * Update an existing comment.
     */
    public function update(Request $request, Event $event, Comment $comment): RedirectResponse
    {
        $this->authorizeComment($event, $comment);
        $event->ensurePurchaserCanLeaveFeedback(Auth::user());

        $validated = $request->validate([
            'body' => 'required|string|min:1|max:1000',
        ]);

        $comment->update([
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Review updated.');
    }

    /**
     * Delete a comment.
     */
    public function destroy(Event $event, Comment $comment): RedirectResponse
    {
        $this->authorizeComment($event, $comment);

        $comment->delete();

        return back()->with('success', 'Review deleted.');
    }

    protected function authorizeComment(Event $event, Comment $comment): void
    {
        $event->ensureFeedbackAllowed();

        if ($comment->event_id !== $event->id) {
            abort(404);
        }

        if ($comment->user_id !== Auth::id()) {
            abort(403);
        }
    }
}
