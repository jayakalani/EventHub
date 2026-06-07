<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Event Categories
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Manage all event categories and their availability.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">

                <a href="{{ route('admin.event.category.create') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 transition">
                    + New Category
                </a>

                <a href="{{ route('admin.event-categories.export.csv') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 transition">
                    Export CSV
                </a>

                <a href="{{ route('admin.event-categories.export.pdf') }}"
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
                    <p class="text-sm text-slate-500">Total Categories</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2">
                        {{ $event_categories->total() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Active Categories</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">
                        {{ $event_categories->where('is_active', true)->count() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Inactive Categories</p>
                    <h3 class="text-3xl font-bold text-rose-600 mt-2">
                        {{ $event_categories->where('is_active', false)->count() }}
                    </h3>
                </div>

            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">

                <form method="GET" action="{{ route('admin.event-categories.index') }}"
                    class="grid grid-cols-1 md:grid-cols-6 gap-3">

                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search category..."
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                    <select name="status"
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">

                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>
                            Inactive
                        </option>
                    </select>

                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="rounded-xl border-slate-300">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="rounded-xl border-slate-300">

                    <button type="submit"
                        class="rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
                        Apply
                    </button>

                    <a href="{{ route('admin.event-categories.index') }}"
                        class="flex items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition">
                        Reset
                    </a>

                </form>

            </div>

            {{-- Success Message --}}
            @if (session('success'))
                <div class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Table --}}
            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

                <div class="px-6 py-5 border-b border-slate-100">
                    <h3 class="text-lg font-semibold text-slate-900">
                        Category Directory
                    </h3>
                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full">

                        <thead class="bg-slate-50">
                            <tr>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    ID
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Category Name
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Created By
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Status
                                </th>

                                <th
                                    class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Actions
                                </th>

                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100">

                            @forelse($event_categories as $category)
                                <tr class="hover:bg-slate-50 transition">

                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-900">
                                            #{{ $category->id }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            {{ $category->name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $category->creator->first_name }}
                                        {{ $category->creator->last_name }}
                                    </td>

                                    <td class="px-6 py-4">

                                        <form action="{{ route('admin.event.category.toggleActive', $category->id) }}"
                                            method="POST">
                                            @csrf

                                            <button
                                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                            {{ $category->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">

                                                {{ $category->is_active ? '● Active' : '● Inactive' }}
                                            </button>

                                        </form>

                                    </td>

                                    <td class="px-6 py-4">

                                        <div class="flex justify-end gap-2">

                                            <a href="{{ route('admin.event.category.edit', $category->id) }}"
                                                class="px-3 py-2 rounded-xl bg-blue-50 text-blue-600 font-medium hover:bg-blue-100 transition">
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.event.category.destroy', $category->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')

                                                <button onclick="return confirm('Delete this category?')"
                                                    class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 font-medium hover:bg-rose-100 transition">
                                                    Delete
                                                </button>

                                            </form>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="5" class="py-16 text-center text-slate-500">
                                        No event categories found.
                                    </td>
                                </tr>
                            @endforelse

                        </tbody>

                    </table>

                </div>

                <div class="border-t border-slate-100 px-6 py-4 bg-slate-50">
                    {{ $event_categories->links() }}
                </div>

            </div>

        </div>
    </div>

</x-app-layout>
