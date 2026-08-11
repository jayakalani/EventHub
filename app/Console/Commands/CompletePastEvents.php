<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CompletePastEvents extends Command
{
    protected $signature = 'events:complete-past';

    protected $description = 'Disabled: event completion is organizer-only (status dropdown after the event date has passed)';

    public function handle(): int
    {
        $this->error('Automatic event completion is disabled.');
        $this->line('Organizers must set status to Completed manually after the event date has passed.');

        return self::FAILURE;
    }
}
