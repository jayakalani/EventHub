<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Audit Logs
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Monitor all system activities, updates, and security-related events.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.audit-logs.export.csv') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 transition">
                    Export CSV
                </a>

                <a href="{{ route('admin.audit-logs.export.pdf') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-rose-600 text-white text-sm font-semibold shadow hover:bg-rose-700 transition">
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Total Logs</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2">
                        {{ $logs->total() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Current Page</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">
                        {{ $logs->currentPage() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Records Per Page</p>
                    <h3 class="text-3xl font-bold text-rose-600 mt-2">
                        {{ $logs->perPage() }}
                    </h3>
                </div>
            </div>

            {{-- Table --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">
                        Activity Directory
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Date
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    User
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Action
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Model
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    ID
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Changes
                                </th>
                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    IP Address
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">
                            @forelse ($logs as $log)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900">
                                            {{ $log->created_at->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $log->created_at->format('h:i A') }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-slate-900">
                                            {{ $log->user?->full_name ?? 'System' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $badgeColor = match (strtolower($log->action)) {
                                                'create' => 'bg-emerald-100 text-emerald-700',
                                                'update' => 'bg-blue-100 text-blue-700',
                                                'delete' => 'bg-rose-100 text-rose-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp

                                        <span
                                            class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $badgeColor }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ class_basename($log->model_type) }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        #{{ $log->model_id }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <details class="group">
                                            <summary
                                                class="cursor-pointer text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                View Changes
                                            </summary>

                                            <div class="mt-3 space-y-3">
                                                @if ($log->old_values)
                                                    <div>
                                                        <p class="text-xs font-semibold text-rose-600 mb-1">
                                                            Old Values
                                                        </p>
                                                        <pre class="bg-rose-50 p-3 rounded-xl text-xs overflow-auto max-w-md">{{ $log->old_values }}</pre>
                                                    </div>
                                                @endif

                                                @if ($log->new_values)
                                                    <div>
                                                        <p class="text-xs font-semibold text-emerald-600 mb-1">
                                                            New Values
                                                        </p>
                                                        <pre class="bg-emerald-50 p-3 rounded-xl text-xs overflow-auto max-w-md">{{ $log->new_values }}</pre>
                                                    </div>
                                                @endif
                                            </div>
                                        </details>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-slate-500 whitespace-nowrap">
                                        {{ $log->ip_address }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-16 text-center text-slate-500">
                                        No audit logs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-6 py-4 bg-slate-50">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
