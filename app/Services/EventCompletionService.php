<?php

namespace App\Services;

/**
 * Automatic past-event completion has been disabled.
 * Organizers mark events completed via the status dropdown after hasPassed().
 *
 * @deprecated Kept only so older references fail closed if reintroduced.
 */
class EventCompletionService
{
    /**
     * @deprecated Automatic bulk completion is disabled.
     */
    public function completePastEvents(): int
    {
        return 0;
    }

    /**
     * @deprecated Automatic single-event completion is disabled.
     */
    public function completeIfPast(\App\Models\Event $event): bool
    {
        return false;
    }
}
