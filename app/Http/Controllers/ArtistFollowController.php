<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\FollowArtist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ArtistFollowController extends Controller
{
    /**
     * Toggle follow status for an artist.
     */
    public function toggle(Artist $artist): RedirectResponse
    {
        if (! $artist->is_active) {
            abort(404);
        }

        $user = Auth::user();

        $follow = FollowArtist::query()
            ->where('user_id', $user->id)
            ->where('artist_id', $artist->id)
            ->first();

        if ($follow) {
            $follow->delete();

            return back()->with('success', 'Unfollowed artist.');
        }

        FollowArtist::create([
            'user_id' => $user->id,
            'artist_id' => $artist->id,
        ]);

        return back()->with('success', 'Now following artist.');
    }
}
