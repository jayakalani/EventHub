<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">CRO Dashboard</h2>
            <p class="mt-1 text-slate-500">Customer Relations Officer workspace</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-6 space-y-8">

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-3xl font-bold text-amber-500">{{ $pendingRefundCount }}</div>
                    <div class="text-slate-600 mt-2">Pending Refunds</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-3xl font-bold text-emerald-600">{{ $processedRefundCount }}</div>
                    <div class="text-slate-600 mt-2">Processed Refunds</div>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('cro.refund-requests.index') }}"
                        class="rounded-2xl bg-indigo-600 text-white p-6 text-center hover:bg-indigo-700 transition font-semibold">
                        Review Refund Requests
                        @if($pendingRefundCount > 0)
                            <span class="ml-2 inline-flex rounded-full bg-white/20 px-2 py-0.5 text-sm">{{ $pendingRefundCount }}</span>
                        @endif
                    </a>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
