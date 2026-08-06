@php
    $statusClass = match ($row['status']) {
        'upcoming' => 'bg-emerald-100 text-emerald-800',
        'ongoing' => 'bg-blue-100 text-blue-800',
        'postponed' => 'bg-orange-100 text-orange-800',
        'completed' => 'bg-slate-200 text-slate-700',
        'cancelled' => 'bg-rose-100 text-rose-800',
        'unpublished' => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-700',
    };
    $fillBarClass = match ($row['status']) {
        'upcoming' => 'bg-emerald-500',
        'ongoing' => 'bg-blue-500',
        'postponed' => 'bg-orange-500',
        'completed' => 'bg-slate-400',
        'cancelled' => 'bg-rose-500',
        'unpublished' => 'bg-amber-400',
        default => 'bg-indigo-500',
    };
    $remainingClass = match (true) {
        $row['remaining'] === 0 => 'text-rose-600',
        $row['remaining'] <= 10 => 'text-amber-700',
        default => 'text-slate-900',
    };
    $delay = $delay ?? 80;
@endphp
<tr class="btn-smooth hover:bg-white/45 {{ $rowClass ?? '' }}">
    <td class="px-5 py-3.5 sm:px-6">
        <a href="{{ $row['url'] }}" class="group block min-w-[11rem]">
            <p class="font-semibold text-slate-900 group-hover:text-indigo-700">
                {{ $row['name'] }}
                @if($row['is_low_inventory'])
                    <i class="bi bi-exclamation-triangle-fill text-amber-500" title="Low inventory"></i>
                @endif
            </p>
            <p class="mt-0.5 text-xs text-slate-500">
                {{ $row['date'] }}
                @if($row['host']) · {{ $row['host'] }} @endif
            </p>
        </a>
    </td>
    <td class="px-3 py-3.5">
        <span class="inline-flex rounded-full px-3 py-1.5 text-sm font-semibold capitalize {{ $statusClass }}">
            {{ $row['status'] }}
        </span>
    </td>
    <td class="whitespace-nowrap px-3 py-3.5 text-slate-700">
        <span class="font-medium">{{ number_format($row['sold']) }}</span>
        <span class="text-slate-400">/ {{ number_format($row['capacity']) }}</span>
    </td>
    <td class="whitespace-nowrap px-3 py-3.5">
        <span class="font-semibold {{ $remainingClass }}">
            {{ number_format($row['remaining']) }}
        </span>
    </td>
    <td class="px-3 py-3.5">
        <div class="flex min-w-[4.5rem] items-center gap-1.5">
            <div class="h-1 w-14 overflow-hidden rounded-full bg-slate-100">
                <div class="progress-fill h-full rounded-full {{ $fillBarClass }}"
                    style="--progress: {{ min(100, $row['fill_rate']) }}%; --progress-delay: {{ $delay }}ms"></div>
            </div>
            <span class="text-[11px] font-semibold text-slate-600">{{ $row['fill_rate'] }}%</span>
        </div>
    </td>
    <td class="whitespace-nowrap bg-rose-50/50 px-5 py-3.5 text-right sm:px-6">
        <span class="font-bold text-rose-700">
            LKR {{ number_format($row['revenue'], 0) }}
        </span>
    </td>
</tr>
