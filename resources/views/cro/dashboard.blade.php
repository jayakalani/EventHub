<x-app-layout>

    <x-slot name="header">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-indigo-600 via-blue-600 to-cyan-500 p-8 shadow-xl">
            <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div>
                    <h1 class="text-3xl font-bold text-white">CRO Dashboard</h1>
                    <p class="mt-2 text-blue-100">Customer Relations Officer workspace</p>
                </div>
                <a href="{{ route('cro.reports') }}"
                    class="inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-semibold text-indigo-600 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                    <i class="bi bi-bar-chart-line"></i>
                    Reports
                </a>
            </div>
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
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-3xl font-bold text-indigo-600">{{ $openInquiryCount + $openComplaintCount }}</div>
                    <div class="text-slate-600 mt-2">Open Inquiries & Complaints</div>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="text-3xl font-bold text-blue-600">{{ $inProgressCount }}</div>
                    <div class="text-slate-600 mt-2">In Progress</div>
                </div>
            </div>

            <div>
                <h2 class="text-xl font-semibold text-slate-900 mb-4">Quick Actions</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                    <a href="{{ route('cro.reports') }}"
                        class="rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white p-6 text-center hover:shadow-lg transition-all duration-300 hover:-translate-y-0.5 font-semibold">
                        <i class="bi bi-bar-chart-line text-2xl block mb-2"></i>
                        View Reports
                    </a>
                    <a href="{{ route('cro.refund-requests.index') }}"
                        class="rounded-2xl bg-indigo-600 text-white p-6 text-center hover:bg-indigo-700 transition font-semibold">
                        Review Refund Requests
                        @if($pendingRefundCount > 0)
                            <span class="ml-2 inline-flex rounded-full bg-white/20 px-2 py-0.5 text-sm">{{ $pendingRefundCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('cro.inquiries.index') }}"
                        class="rounded-2xl bg-purple-600 text-white p-6 text-center hover:bg-purple-700 transition font-semibold">
                        Manage Inquiries
                        @if($openInquiryCount > 0)
                            <span class="ml-2 inline-flex rounded-full bg-white/20 px-2 py-0.5 text-sm">{{ $openInquiryCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('cro.complaints.index') }}"
                        class="rounded-2xl bg-rose-600 text-white p-6 text-center hover:bg-rose-700 transition font-semibold">
                        Manage Complaints
                        @if($openComplaintCount > 0)
                            <span class="ml-2 inline-flex rounded-full bg-white/20 px-2 py-0.5 text-sm">{{ $openComplaintCount }}</span>
                        @endif
                    </a>
                    <a href="{{ route('cro.reports', ['tab' => 'inquiries']) }}"
                        class="rounded-2xl border border-slate-200 bg-white text-slate-900 p-6 text-center hover:bg-slate-50 transition font-semibold shadow-sm">
                        Inquiry Reports
                    </a>
                </div>
            </div>

        </div>
    </div>

</x-app-layout>
