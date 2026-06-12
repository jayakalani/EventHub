<x-app-layout>
    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">Support Reports</h1>
                    <p class="mt-2 text-blue-100">Monitor inquiries, complaints, and CRO performance.</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('admin.support-reports.export.csv') }}"
                        class="inline-flex items-center rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition">
                        Export CSV
                    </a>
                    <a href="{{ route('admin.support-reports.export.pdf') }}"
                        class="inline-flex items-center rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition">
                        Export PDF
                    </a>
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center rounded-2xl border border-white/30 bg-white/10 backdrop-blur-sm px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total Inquiries</p>
                    <h3 class="mt-2 text-4xl font-bold text-indigo-600">{{ $totalInquiries }}</h3>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total Complaints</p>
                    <h3 class="mt-2 text-4xl font-bold text-purple-600">{{ $totalComplaints }}</h3>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Resolved Jobs</p>
                    <h3 class="mt-2 text-4xl font-bold text-emerald-600">{{ $resolvedCount }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $resolvedInquiries }} inquiries · {{ $resolvedComplaints }} complaints</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Pending Jobs</p>
                    <h3 class="mt-2 text-4xl font-bold text-amber-600">{{ $pendingCount }}</h3>
                    <p class="mt-1 text-xs text-slate-500">{{ $pendingInquiries }} inquiries · {{ $pendingComplaints }} complaints</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Recent Inquiries</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentInquiries as $inquiry)
                            <div class="px-6 py-4">
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $inquiry->subject }}</p>
                                        <p class="text-sm text-slate-500">{{ $inquiry->user->full_name }} · {{ $inquiry->event->name }}</p>
                                    </div>
                                    @include('partials.support-status-badge', ['status' => $inquiry->status])
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-center text-slate-500">No inquiries yet.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-6 py-5">
                        <h3 class="text-lg font-bold text-slate-900">Recent Complaints</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @forelse($recentComplaints as $complaint)
                            <div class="px-6 py-4">
                                <div class="flex justify-between gap-3">
                                    <div>
                                        <p class="font-semibold text-slate-900">{{ $complaint->subject }}</p>
                                        <p class="text-sm text-slate-500">{{ $complaint->user->full_name }}</p>
                                    </div>
                                    @include('partials.support-status-badge', ['status' => $complaint->status])
                                </div>
                            </div>
                        @empty
                            <p class="px-6 py-8 text-center text-slate-500">No complaints yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
