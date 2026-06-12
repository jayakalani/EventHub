@props(['status'])

@php
    $enum = $status instanceof \App\Enums\SupportTicketStatusEnum
        ? $status
        : \App\Enums\SupportTicketStatusEnum::from($status);
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-3 py-1 text-xs font-semibold '.$enum->badgeClass()]) }}>
    {{ $enum->label() }} — {{ $enum->description() }}
</span>
