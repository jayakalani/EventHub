<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">
                    Audit Logs
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Monitor all system activities, updates, and security-related events.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.audit-logs.export.csv') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 text-sm font-medium text-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                    </svg>
                    Export CSV
                </a>

                <a href="{{ route('admin.audit-logs.export.pdf') }}"
                    class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-200 text-sm font-medium">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 16v-8m0 8l-3-3m3 3l3-3M5 20h14" />
                    </svg>
                    Export PDF
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Statistics --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">Total Logs</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-1">
                        {{ $logs->total() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">Current Page</p>
                    <h3 class="text-3xl font-bold text-indigo-600 mt-1">
                        {{ $logs->currentPage() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
                    <p class="text-sm text-gray-500">Records Per Page</p>
                    <h3 class="text-3xl font-bold text-green-600 mt-1">
                        {{ $logs->perPage() }}
                    </h3>
                </div>
            </div>

            {{-- Main Card --}}
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

                <div class="px-6 py-5 border-b border-gray-100">
                    <h3 class="text-xl font-semibold text-gray-900">
                        Recent Activity Logs
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">
                        Detailed audit trail of all important system actions.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Date
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    User
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Action
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Model
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    Changes
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase">
                                    IP Address
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50 transition">

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $log->created_at->format('d M Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $log->created_at->format('h:i A') }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">
                                            {{ $log->user?->full_name ?? 'System' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            $badgeColor = match(strtolower($log->action)) {
                                                'create' => 'bg-green-100 text-green-700',
                                                'update' => 'bg-blue-100 text-blue-700',
                                                'delete' => 'bg-red-100 text-red-700',
                                                default => 'bg-gray-100 text-gray-700',
                                            };
                                        @endphp

                                        <span
                                            class="inline-flex px-3 py-1 rounded-full text-xs font-semibold {{ $badgeColor }}">
                                            {{ ucfirst($log->action) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-700">
                                            {{ class_basename($log->model_type) }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-600">
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
                                                        <p class="text-xs font-semibold text-red-600 mb-1">
                                                            Old Values
                                                        </p>
                                                        <pre class="bg-red-50 p-3 rounded-xl text-xs overflow-auto max-w-md">{{ $log->old_values }}</pre>
                                                    </div>
                                                @endif

                                                @if ($log->new_values)
                                                    <div>
                                                        <p class="text-xs font-semibold text-green-600 mb-1">
                                                            New Values
                                                        </p>
                                                        <pre class="bg-green-50 p-3 rounded-xl text-xs overflow-auto max-w-md">{{ $log->new_values }}</pre>
                                                    </div>
                                                @endif

                                            </div>
                                        </details>
                                    </td>

                                    <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                        {{ $log->ip_address }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16 text-center">

                                        <div class="flex flex-col items-center">
                                            <svg class="w-14 h-14 text-gray-300 mb-4"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.5"
                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                                            </svg>

                                            <h3 class="text-lg font-semibold text-gray-700">
                                                No Audit Logs Found
                                            </h3>

                                            <p class="text-sm text-gray-500 mt-2">
                                                System activity will appear here once actions are performed.
                                            </p>
                                        </div>

                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 px-6 py-4 bg-gray-50">
                    {{ $logs->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>