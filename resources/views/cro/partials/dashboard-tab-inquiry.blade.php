{{-- Inquiry analytics: status mix, resolution trend, response time --}}
@php
    $inquiries = $reports['inquiries'];
    $activeInsightFilters = $reports['filters'] ?? ['event' => null, 'from' => null, 'to' => null];
    $selectedEventName = $eventFilter['selectedEventName']
        ?? ($activeInsightFilters['selectedEventName'] ?? null);
@endphp

<div class="space-y-5" id="cro-inquiry">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 items-center justify-center rounded-xl border border-sky-200/60 bg-sky-50/80 text-sky-600 shadow-sm">
                <i class="bi bi-chat-left-text text-sm"></i>
            </span>
            <div>
                <h2 class="text-base font-bold tracking-tight text-slate-900">Inquiry</h2>
                <p class="mt-0.5 text-xs text-slate-500 sm:text-sm">
                    Status mix, response speed, and volume by event
                    @if ($selectedEventName)
                        · <span class="font-medium text-slate-700">{{ $selectedEventName }}</span>
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('cro.inquiries.index') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-sky-200 bg-sky-50 px-3.5 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-100">
            <i class="bi bi-envelope-open"></i>
            Open inquiries
        </a>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($inquiries['statusBreakdown'] as $status)
            <div class="glass-card !p-3 text-center hover:!-translate-y-0.5">
                <p class="text-xl font-bold text-slate-900">{{ $status['count'] }}</p>
                <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $status['label'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Status distribution</h3>
            <p class="mt-0.5 text-sm text-slate-500">Open through closed</p>
            <div class="mt-4 h-64 sm:h-72">
                <canvas id="inquiryStatusChart"></canvas>
            </div>
        </section>
        <section class="glass-card p-4 sm:p-5 xl:col-span-2">
            <h3 class="text-base font-bold text-slate-900">Inquiry vs resolution</h3>
            <p class="mt-0.5 text-sm text-slate-500">Submitted vs resolved in range</p>
            <div class="mt-4 h-64 sm:h-72">
                <canvas id="inquiryResolutionTrendChart"></canvas>
            </div>
        </section>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Average response time</h3>
            <p class="mt-0.5 text-sm text-slate-500">Minutes to first response</p>
            <div class="mt-4 h-60">
                <canvas id="inquiryResponseTimeChart"></canvas>
            </div>
        </section>
        <section class="glass-card p-4 sm:p-5">
            <h3 class="text-base font-bold text-slate-900">Inquiries by event</h3>
            <p class="mt-0.5 text-sm text-slate-500">Highest volume events</p>
            <div class="mt-4 h-60">
                <canvas id="inquiryByEventChart"></canvas>
            </div>
        </section>
    </div>
</div>
