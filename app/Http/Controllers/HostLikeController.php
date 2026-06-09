<?php

namespace App\Http\Controllers;

use App\Models\Host;
use App\Models\HostLike;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class HostLikeController extends Controller
{
    /**
     * Toggle like status for a host.
     */
    public function toggle(Host $host): RedirectResponse
    {
        if (! $host->is_active) {
            abort(404);
        }

        $user = Auth::user();

        $hostLike = HostLike::query()
            ->where('user_id', $user->id)
            ->where('host_id', $host->id)
            ->first();

        if ($hostLike) {
            $hostLike->delete();

            return back()->with('success', 'Host unliked.');
        }

        HostLike::create([
            'user_id' => $user->id,
            'host_id' => $host->id,
        ]);

        return back()->with('success', 'Host liked!');
    }
}
