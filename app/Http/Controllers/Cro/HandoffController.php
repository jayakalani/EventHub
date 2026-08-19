<?php

namespace App\Http\Controllers\Cro;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\CroHandoffService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HandoffController extends Controller
{
    public function __construct(
        protected CroHandoffService $handoffService,
    ) {}

    public function show(Event $event): View
    {
        abort_unless($event->isAssignedToCro(Auth::id()), 403);
        abort_unless($event->isPostponed() || $event->isCancelled(), 404);

        $handoff = $this->handoffService->forEvent($event);

        return view('cro.handoffs.show', compact('event', 'handoff'));
    }
}
