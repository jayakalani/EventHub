<?php

namespace App\Console\Commands;

use App\Services\EventCompletionService;
use Illuminate\Console\Command;

class CompletePastEvents extends Command
{
    protected $signature = 'events:complete-past';

    protected $description = 'Mark past events as completed once their event date has passed';

    public function handle(EventCompletionService $eventCompletionService): int
    {
        $count = $eventCompletionService->completePastEvents();

        $this->info("Marked {$count} event(s) as completed.");

        return self::SUCCESS;
    }
}
