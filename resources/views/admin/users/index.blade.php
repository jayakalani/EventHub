<x-app-layout>
    @php
        $roleLabels = [
            'admin' => 'Admin',
            'event organizer' => 'Organizer',
            'customer relations officer' => 'CRO',
            'attendee' => 'Attendee',
        ];

        $accountStatusLabels = [
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];

        $lockStatusLabels = [
            'locked' => 'Locked',
            'unlocked' => 'Unlocked',
        ];

        $emailLabels = [
            'yes' => 'Verified',
            'no' => 'Not verified',
        ];

        $filterScope = $hasActiveFilters ? 'Within current filters' : 'All platform users';

        $kpis = [
            [
                'label' => 'Matched',
                'value' => $stats['matched'],
                'sub' => $hasActiveFilters ? 'After active filters' : 'All platform users',
                'icon' => 'bi-people',
                'accent' => 'indigo',
            ],
            [
                'label' => 'Active',
                'value' => $stats['active'],
                'sub' => $filterScope,
                'icon' => 'bi-person-check',
                'accent' => 'emerald',
            ],
            [
                'label' => 'Inactive',
                'value' => $stats['inactive'],
                'sub' => $filterScope,
                'icon' => 'bi-person-dash',
                'accent' => 'rose',
            ],
            [
                'label' => 'Locked',
                'value' => $stats['locked'],
                'sub' => $filterScope,
                'icon' => 'bi-lock',
                'accent' => 'amber',
            ],
        ];

        $activeFilterChips = array_filter([
            'search' => request('search') ? 'Search: '.request('search') : null,
            'role' => request('role') ? 'Role: '.($roleLabels[request('role')] ?? request('role')) : null,
            'account_status' => request('account_status') ? 'Account: '.($accountStatusLabels[request('account_status')] ?? request('account_status')) : null,
            'lock_status' => request('lock_status') ? 'Lock: '.($lockStatusLabels[request('lock_status')] ?? request('lock_status')) : null,
            'status' => (! request('account_status') && ! request('lock_status') && request('status'))
                ? 'Status: '.request('status')
                : null,
            'email_state' => request('email_state') ? 'Email: '.($emailLabels[request('email_state')] ?? request('email_state')) : null,
            'staff_only' => request()->boolean('staff_only') ? 'Staff roles only' : null,
            'from_date' => request('from_date') ? 'From: '.request('from_date') : null,
            'to_date' => request('to_date') ? 'To: '.request('to_date') : null,
        ]);
    @endphp

    <div class="admin-users relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Hero --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/25 blur-2xl"></div>

                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600/90 text-white shadow-sm ring-2 ring-white/70 sm:h-10 sm:w-10">
                                    <i class="bi bi-people text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Access management</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Users
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Manage roles, account availability, and verification ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.employees.create') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg bg-indigo-600/95 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md sm:text-sm">
                                <i class="bi bi-person-plus"></i>
                                New Employee
                            </a>
                            <a href="{{ route('admin.employees.export.csv', request()->query()) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-filetype-csv"></i>
                                Export CSV
                            </a>
                            <a href="{{ route('admin.employees.export.pdf', request()->query()) }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-file-earmark-pdf"></i>
                                Export PDF
                            </a>
                            <a href="{{ route('dashboard') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            {{-- KPI snapshot --}}
            <section class="space-y-3">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">User snapshot</h2>
                    <p class="text-xs text-slate-500">Account health for the current result set.</p>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($kpis as $kpi)
                        @php
                            $accent = match ($kpi['accent']) {
                                'indigo' => ['top' => 'border-t-indigo-500', 'iconBg' => 'bg-indigo-100/70', 'iconText' => 'text-indigo-600', 'value' => 'text-indigo-700'],
                                'emerald' => ['top' => 'border-t-emerald-500', 'iconBg' => 'bg-emerald-100/70', 'iconText' => 'text-emerald-600', 'value' => 'text-emerald-700'],
                                'rose' => ['top' => 'border-t-rose-500', 'iconBg' => 'bg-rose-100/70', 'iconText' => 'text-rose-600', 'value' => 'text-rose-700'],
                                default => ['top' => 'border-t-amber-500', 'iconBg' => 'bg-amber-100/70', 'iconText' => 'text-amber-600', 'value' => 'text-amber-700'],
                            };
                        @endphp
                        <div class="glass-card kpi-lift group border-t-4 {{ $accent['top'] }} p-4 sm:p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {{ $kpi['label'] }}
                                    </p>
                                    <p class="mt-1 truncate text-2xl font-bold tracking-tight {{ $accent['value'] }}">
                                        {{ number_format($kpi['value']) }}
                                    </p>
                                    <p class="mt-1 text-xs font-medium text-slate-500">{{ $kpi['sub'] }}</p>
                                </div>
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $accent['iconBg'] }} backdrop-blur-sm transition-transform duration-300 group-hover:scale-110">
                                    <i class="bi {{ $kpi['icon'] }} text-lg {{ $accent['iconText'] }}"></i>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Filters --}}
            <section class="glass-panel !rounded-2xl p-4 sm:p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Filter users</h2>
                        <p class="text-xs text-slate-500">Search by name, role, status, email verification, or date.</p>
                    </div>

                    @if ($hasActiveFilters)
                        <a href="{{ route('admin.users') }}"
                            class="btn-smooth inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                            <i class="bi bi-x-circle"></i>
                            Clear all filters
                        </a>
                    @endif
                </div>

                <form method="GET" action="{{ route('admin.users') }}"
                    class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-12">
                    @if (request()->boolean('staff_only'))
                        <input type="hidden" name="staff_only" value="1">
                    @endif
                    <div class="xl:col-span-3">
                        <label for="users_search" class="mb-1.5 block text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                            <input id="users_search" type="text" name="search" value="{{ request('search') }}"
                                placeholder="Name, email, contact..."
                                class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-9 pr-3 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="xl:col-span-2">
                        <label for="users_role" class="mb-1.5 block text-xs font-semibold text-slate-600">Role</label>
                        <select id="users_role" name="role"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All roles</option>
                            @foreach ($roleLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-1">
                        <label for="users_account" class="mb-1.5 block text-xs font-semibold text-slate-600">Account</label>
                        <select id="users_account" name="account_status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($accountStatusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('account_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-1">
                        <label for="users_lock" class="mb-1.5 block text-xs font-semibold text-slate-600">Lock</label>
                        <select id="users_lock" name="lock_status"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($lockStatusLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('lock_status') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-1">
                        <label for="users_email" class="mb-1.5 block text-xs font-semibold text-slate-600">Email</label>
                        <select id="users_email" name="email_state"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 pl-3 pr-9 text-sm font-medium text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All</option>
                            @foreach ($emailLabels as $value => $label)
                                <option value="{{ $value }}" @selected(request('email_state') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="xl:col-span-1">
                        <label for="users_from" class="mb-1.5 block text-xs font-semibold text-slate-600">From</label>
                        <input id="users_from" type="date" name="from_date" value="{{ request('from_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="xl:col-span-2">
                        <label for="users_to" class="mb-1.5 block text-xs font-semibold text-slate-600">To</label>
                        <input id="users_to" type="date" name="to_date" value="{{ request('to_date') }}"
                            class="w-full rounded-lg border border-white/70 bg-white/60 py-2 px-3 text-sm text-slate-700 shadow-sm backdrop-blur-md focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="flex items-end gap-2 xl:col-span-1">
                        <button type="submit"
                            class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                    </div>
                </form>

                @if ($hasActiveFilters)
                    <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/60 pt-4">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Active</span>
                        @foreach ($activeFilterChips as $chip)
                            <span
                                class="inline-flex items-center rounded-full border border-indigo-100 bg-indigo-50/80 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $chip }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </section>

            @if (session('success'))
                <div
                    class="glass-panel !rounded-2xl border border-emerald-200/70 bg-emerald-50/70 px-4 py-3 text-sm font-medium text-emerald-700 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-check-circle-fill"></i>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div
                    class="glass-panel !rounded-2xl border border-rose-200/70 bg-rose-50/70 px-4 py-3 text-sm font-medium text-rose-700 shadow-sm backdrop-blur">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        {{ session('error') }}
                    </div>
                </div>
            @endif

            {{-- Directory table --}}
            <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                <div class="flex flex-col gap-2 border-b border-white/60 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">User directory</h2>
                        <p class="text-xs text-slate-500">
                            Showing
                            <span class="font-semibold text-slate-700">{{ $users->firstItem() ?? 0 }}</span>–
                            <span class="font-semibold text-slate-700">{{ $users->lastItem() ?? 0 }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ number_format($users->total()) }}</span>
                            users
                        </p>
                    </div>
                    <p class="text-xs font-medium text-slate-500">
                        Page {{ $users->currentPage() }} of {{ max(1, $users->lastPage()) }}
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-white/40 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                                <th class="px-4 py-3 sm:px-6">User</th>
                                <th class="px-4 py-3 sm:px-6">Contact</th>
                                <th class="px-4 py-3 sm:px-6">Role</th>
                                <th class="px-4 py-3 sm:px-6">Status</th>
                                <th class="px-4 py-3 text-right sm:px-6">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100/80">
                            @forelse ($users as $user)
                                @php
                                    $initials = strtoupper(
                                        substr($user->first_name ?? 'U', 0, 1).substr($user->last_name ?? '', 0, 1)
                                    );
                                    $roleName = $user->userRole->name_en ?? 'N/A';
                                    $roleBadge = match (strtolower($roleName)) {
                                        'admin' => 'bg-indigo-100 text-indigo-700 ring-indigo-200/70',
                                        'event organizer' => 'bg-violet-100 text-violet-700 ring-violet-200/70',
                                        'customer relations officer' => 'bg-cyan-100 text-cyan-700 ring-cyan-200/70',
                                        'attendee' => 'bg-slate-100 text-slate-700 ring-slate-200/70',
                                        default => 'bg-slate-100 text-slate-700 ring-slate-200/70',
                                    };
                                    $isSelf = $user->isCurrentAuthUser();
                                    $canToggleLock = $user->adminProtectionError('lock') === null;
                                    $canToggleActive = $user->adminProtectionError('deactivate') === null;
                                    $canDelete = $user->adminProtectionError('delete') === null;
                                @endphp

                                <tr class="btn-smooth align-middle hover:bg-white/45">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500/90 to-cyan-500/80 text-xs font-bold text-white shadow-sm ring-2 ring-white/80">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate text-sm font-semibold text-slate-900">
                                                        {{ $user->full_name }}
                                                    </p>
                                                    <span class="font-mono text-[11px] text-slate-400">#{{ $user->id }}</span>
                                                    @if ($isSelf)
                                                        <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-100">You</span>
                                                    @endif
                                                </div>
                                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $user->email }}</p>
                                                <div class="mt-1">
                                                    @if ($user->email_verified_at)
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 ring-1 ring-emerald-100">
                                                            <i class="bi bi-patch-check-fill"></i>
                                                            Verified
                                                        </span>
                                                    @else
                                                        <span
                                                            class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-100">
                                                            <i class="bi bi-exclamation-circle"></i>
                                                            Unverified
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="inline-flex items-center gap-1.5 text-sm text-slate-600">
                                            <i class="bi bi-telephone text-slate-400"></i>
                                            {{ $user->contact_number ?: '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $roleBadge }}">
                                            {{ $roleLabels[strtolower($roleName)] ?? ucfirst($roleName) }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex flex-wrap gap-2">
                                            @if ($canToggleLock && ! $isSelf)
                                                <form action="{{ route('admin.user.toggleLock', $user->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Are you sure you want to {{ $user->is_locked ? 'unlock' : 'lock' }} {{ $user->full_name }}?')"
                                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition hover:-translate-y-0.5 hover:shadow-sm
                                                        {{ $user->is_locked
                                                            ? 'bg-amber-100 text-amber-700 ring-amber-200/70 hover:bg-amber-200/80'
                                                            : 'bg-emerald-100 text-emerald-700 ring-emerald-200/70 hover:bg-emerald-200/80' }}">
                                                        <i class="bi {{ $user->is_locked ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                                                        {{ $user->is_locked ? 'Locked' : 'Unlocked' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span
                                                    title="{{ $isSelf ? 'You cannot change your own lock status here' : 'Protected admin account' }}"
                                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset
                                                    {{ $user->is_locked
                                                        ? 'bg-amber-50 text-amber-700 ring-amber-200/70'
                                                        : 'bg-emerald-50 text-emerald-700 ring-emerald-200/70' }}">
                                                    <i class="bi {{ $user->is_locked ? 'bi-lock-fill' : 'bi-unlock-fill' }}"></i>
                                                    {{ $user->is_locked ? 'Locked' : 'Unlocked' }}
                                                </span>
                                            @endif

                                            @if ($canToggleActive && ! $isSelf)
                                                <form action="{{ route('admin.user.toggleActive', $user->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit"
                                                        onclick="return confirm('Are you sure you want to set {{ $user->full_name }} as {{ $user->is_active ? 'inactive' : 'active' }}?')"
                                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset transition hover:-translate-y-0.5 hover:shadow-sm
                                                        {{ $user->is_active
                                                            ? 'bg-emerald-100 text-emerald-700 ring-emerald-200/70 hover:bg-emerald-200/80'
                                                            : 'bg-rose-100 text-rose-700 ring-rose-200/70 hover:bg-rose-200/80' }}">
                                                        <i class="bi {{ $user->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                                    </button>
                                                </form>
                                            @else
                                                <span
                                                    title="{{ $isSelf ? 'You cannot change your own account status here' : 'Protected admin account' }}"
                                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset
                                                    {{ $user->is_active
                                                        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200/70'
                                                        : 'bg-rose-50 text-rose-700 ring-rose-200/70' }}">
                                                    <i class="bi {{ $user->is_active ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.user.edit', $user->id) }}"
                                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-indigo-100 bg-indigo-50/80 px-3 py-2 text-xs font-semibold text-indigo-700 backdrop-blur hover:-translate-y-0.5 hover:bg-indigo-100 hover:shadow-sm">
                                                <i class="bi bi-pencil-square"></i>
                                                Edit
                                            </a>

                                            @if ($canDelete && ! $isSelf)
                                                <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        onclick="return confirm('Are you sure you want to delete this user?')"
                                                        class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-rose-100 bg-rose-50/80 px-3 py-2 text-xs font-semibold text-rose-700 backdrop-blur hover:-translate-y-0.5 hover:bg-rose-100 hover:shadow-sm">
                                                        <i class="bi bi-trash3"></i>
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-16 text-center">
                                        <div
                                            class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <i class="bi bi-people text-xl"></i>
                                        </div>
                                        <p class="mt-3 text-sm font-semibold text-slate-700">No users found</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            @if ($hasActiveFilters)
                                                Try adjusting or clearing your filters.
                                            @else
                                                Create an employee to get started.
                                            @endif
                                        </p>
                                        @if ($hasActiveFilters)
                                            <a href="{{ route('admin.users') }}"
                                                class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                Clear filters
                                            </a>
                                        @else
                                            <a href="{{ route('admin.employees.create') }}"
                                                class="btn-smooth mt-4 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                                <i class="bi bi-person-plus"></i>
                                                New Employee
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="border-t border-white/60 bg-white/30 px-4 py-4 sm:px-6">
                        {{ $users->links() }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
