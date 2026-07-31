<?php

namespace App\Http\Controllers;

use App\Models\FollowHost;
use App\Models\Host;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HostFollowController extends Controller
{
    /**
     * Toggle follow status for a host.
     */
    public function toggle(Host $host): RedirectResponse
    {
        if (! $host->is_active) {
            abort(404);
        }

        $user = Auth::user();

        $follow = FollowHost::query()
            ->where('user_id', $user->id)
            ->where('host_id', $host->id)
            ->first();

        if ($follow) {
            $follow->delete();

            return back()->with('success', 'Unfollowed host.');
        }

        FollowHost::create([
            'user_id' => $user->id,
            'host_id' => $host->id,
        ]);

        return back()->with('success', 'Now following host.');
    }
}
