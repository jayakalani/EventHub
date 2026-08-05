<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Users
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Manage all system users, roles, and account availability.
                </p>
            </div>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.employees.create') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-semibold shadow hover:bg-indigo-700 transition">
                    + New Employee
                </a>

                <a href="{{ route('admin.employees.export.csv') }}"
                    class="inline-flex items-center px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-sm font-semibold shadow hover:bg-emerald-700 transition">
                    Export CSV
                </a>

                <a href="{{ route('admin.employees.export.pdf') }}"
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
                    <p class="text-sm text-slate-500">Total Users</p>
                    <h3 class="text-3xl font-bold text-slate-900 mt-2">
                        {{ $users->total() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Active Users</p>
                    <h3 class="text-3xl font-bold text-emerald-600 mt-2">
                        {{ $users->where('is_active', true)->count() }}
                    </h3>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
                    <p class="text-sm text-slate-500">Inactive Users</p>
                    <h3 class="text-3xl font-bold text-rose-600 mt-2">
                        {{ $users->where('is_active', false)->count() }}
                    </h3>
                </div>
            </div>

            {{-- Filters --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
                <form method="GET" action="{{ route('admin.users') }}" class="grid grid-cols-1 md:grid-cols-8 gap-3">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search users..."
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 md:col-span-2">

                    <select name="role"
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Roles</option>
                        <option value="admin" {{ request('role') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="event organizer" {{ request('role') == 'event organizer' ? 'selected' : '' }}>
                            Organizer
                        </option>
                        <option value="customer relations officer"
                            {{ request('role') == 'customer relations officer' ? 'selected' : '' }}>
                            CRO
                        </option>
                        <option value="attendee" {{ request('role') == 'attendee' ? 'selected' : '' }}>Attendee
                        </option>
                    </select>

                    <select name="status"
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive
                        </option>
                        <option value="lock" {{ request('status') == 'lock' ? 'selected' : '' }}>Locked</option>
                        <option value="unlocked" {{ request('status') == 'unlocked' ? 'selected' : '' }}>Unlocked
                        </option>
                    </select>

                    <select name="email_state"
                        class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Email</option>
                        <option value="yes" {{ request('email_state') == 'yes' ? 'selected' : '' }}>Verified
                        </option>
                        <option value="no" {{ request('email_state') == 'no' ? 'selected' : '' }}>Not Verified
                        </option>
                    </select>

                    <input type="date" name="from_date" value="{{ request('from_date') }}"
                        class="rounded-xl border-slate-300">

                    <input type="date" name="to_date" value="{{ request('to_date') }}"
                        class="rounded-xl border-slate-300">

                    <div class="grid grid-cols-2 gap-3 md:col-span-8 lg:col-span-1 lg:grid-cols-1">
                        <button type="submit"
                            class="rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition">
                            Apply
                        </button>

                        <a href="{{ route('admin.users') }}"
                            class="flex items-center justify-center rounded-xl bg-slate-100 text-slate-700 font-medium hover:bg-slate-200 transition">
                            Reset
                        </a>
                    </div>
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
                        User Directory
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
                                    Name
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Email
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Contact
                                </th>

                                <th
                                    class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                    Role
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
                            @forelse ($users as $user)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4">
                                        <span class="font-medium text-slate-900">
                                            #{{ $user->id }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="font-semibold text-slate-900">
                                            {{ $user->full_name }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $user->email }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $user->contact_number ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4 text-slate-600">
                                        {{ $user->userRole->name_en ?? 'N/A' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <form action="{{ route('admin.user.toggleLock', $user->id) }}"
                                                method="POST">
                                                @csrf

                                                <button type="submit"
                                                    onclick="return confirm('Are you sure you want to {{ $user->is_locked ? 'unlock' : 'lock' }} {{ $user->full_name }}?')"
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                                    {{ $user->is_locked ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $user->is_locked ? '● Locked' : '● Unlocked' }}
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.user.toggleActive', $user->id) }}"
                                                method="POST">
                                                @csrf

                                                <button type="submit"
                                                    onclick="return confirm('Are you sure you want to set {{ $user->full_name }} as {{ $user->is_active ? 'inactive' : 'active' }}?')"
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold
                                                    {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700' }}">
                                                    {{ $user->is_active ? '● Active' : '● Inactive' }}
                                                </button>
                                            </form>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.user.edit', $user->id) }}"
                                                class="px-3 py-2 rounded-xl bg-blue-50 text-blue-600 font-medium hover:bg-blue-100 transition">
                                                Edit
                                            </a>

                                            <form action="{{ route('admin.user.destroy', $user->id) }}"
                                                method="POST">
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    onclick="return confirm('Are you sure you want to delete this user?')"
                                                    class="px-3 py-2 rounded-xl bg-rose-50 text-rose-600 font-medium hover:bg-rose-100 transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-16 text-center text-slate-500">
                                        No users found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 px-6 py-4 bg-slate-50">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
