@php
    use App\Enums\SupportTicketStatusEnum;
    use App\Support\CroSupportSla;

    $isOpen = in_array($ticket->status, [SupportTicketStatusEnum::Open, SupportTicketStatusEnum::InProgress], true);
    $level = CroSupportSla::level($ticket->created_at, $ticket->subject, $isOpen);
    $ageLabel = CroSupportSla::ageLabel($ticket->created_at);
@endphp

<div class="flex flex-wrap items-center gap-2">
    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ CroSupportSla::levelClass($level) }}">
        @if ($level === 'urgent')
            <i class="bi bi-exclamation-triangle-fill text-[10px]"></i>
        @elseif ($level === 'overdue' || $level === 'aging')
            <i class="bi bi-clock-history text-[10px]"></i>
        @endif
        {{ CroSupportSla::levelLabel($level) }}
    </span>
    <span class="text-xs {{ CroSupportSla::ageClass($level) }}" title="{{ $ticket->created_at?->format('d M Y, H:i') }}">
        Age {{ $ageLabel }}
    </span>
</div>
