@php
    $queueScope = $queueScope ?? 'mine';
    $status = $status ?? null;
    $mineLabel = $mineLabel ?? 'My queue';
    $allLabel = $allLabel ?? 'All';
    $mineHint = $mineHint ?? null;
    $extraQuery = $extraQuery ?? [];
    $queryBase = array_filter(array_merge([
        'status' => $status,
    ], $extraQuery), fn ($value) => $value !== null && $value !== '');
@endphp

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="inline-flex rounded-2xl border border-slate-200 bg-white p-1 shadow-sm">
        <a href="{{ route($routeName, $queryBase) }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $queueScope === 'mine' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            {{ $mineLabel }}
        </a>
        <a href="{{ route($routeName, array_merge($queryBase, ['scope' => 'all'])) }}"
            class="rounded-xl px-4 py-2 text-sm font-semibold transition {{ $queueScope === 'all' ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-50' }}">
            {{ $allLabel }}
        </a>
    </div>
    @if ($mineHint && $queueScope === 'mine')
        <p class="text-sm text-slate-500">{{ $mineHint }}</p>
    @endif
</div>
