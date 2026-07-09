@props(['title', 'description' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 rounded-2xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white px-6 py-4 shadow-sm sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h2 class="text-lg font-bold text-slate-900">{{ $title }}</h2>
        @if($description)
            <p class="text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</div>
