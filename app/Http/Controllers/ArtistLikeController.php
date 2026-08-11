<?php

namespace App\Http\Controllers;

use App\Models\Artist;
use App\Models\ArtistLike;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ArtistLikeController extends Controller
{
    /**
     * Toggle like status for an artist.
     */
    public function toggle(Artist $artist): RedirectResponse
    {
        if (! $artist->is_active) {
            abort(404);
        }

        $user = Auth::user();

        $artistLike = ArtistLike::query()
            ->where('user_id', $user->id)
            ->where('artist_id', $artist->id)
            ->first();

        if ($artistLike) {
            $artistLike->delete();

            return back()->with('success', 'Artist unliked.');
        }

        ArtistLike::create([
            'user_id' => $user->id,
            'artist_id' => $artist->id,
        ]);

        return back()->with('success', 'Artist liked!');
    }
}
