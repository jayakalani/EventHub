<?php

namespace App\Services\OrganizerReports\Contracts;

use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

interface ReportGenerator
{
    /**
     * @param  array<int, string>  $fields
     * @param  array<string, mixed>  $filters
     */
    public function generate(User $user, array $fields, array $filters, string $format): Response;
}
