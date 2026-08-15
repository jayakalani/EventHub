<x-app-layout>
    @php
        $fieldClass = 'w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2.5 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500';
        $labelClass = 'mb-1.5 block text-xs font-semibold text-slate-600';
        $initials = strtoupper(substr($user->first_name ?? 'U', 0, 1).substr($user->last_name ?? '', 0, 1));
        $roleChangeLocked = $roleChangeLocked ?? false;
        $emailChangeLocked = $emailChangeLocked ?? false;
        $organizerAssets = $organizerAssets ?? 0;
        $croAssets = $croAssets ?? 0;
        $otherOrganizers = $otherOrganizers ?? collect();
        $otherCros = $otherCros ?? collect();
        $isSelf = $user->isCurrentAuthUser();
    @endphp

    <div class="admin-edit-user relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-5xl space-y-5 px-4 sm:px-6 lg:px-8">

            {{-- Hero --}}
            <section class="glass-panel overflow-hidden !rounded-2xl">
                <div class="relative px-4 py-4 sm:px-6 sm:py-5">
                    <div class="pointer-events-none absolute -right-10 -top-10 h-36 w-36 rounded-full bg-indigo-200/35 blur-2xl"></div>
                    <div class="pointer-events-none absolute -bottom-12 left-1/4 h-28 w-28 rounded-full bg-cyan-200/25 blur-2xl"></div>

                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2.5">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500/90 to-cyan-500/80 text-xs font-bold text-white shadow-sm ring-2 ring-white/70 sm:h-11 sm:w-11">
                                    {{ $initials }}
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Access management</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Edit User
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 truncate text-sm text-slate-500">
                                {{ $user->full_name }} · {{ $user->email }} · #{{ $user->id }}
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-semibold text-indigo-700 ring-1 ring-indigo-100">
                                    {{ $user->userRole?->name_en ?? 'No role' }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $user->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-rose-50 text-rose-700 ring-rose-100' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $user->is_locked ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">
                                    {{ $user->is_locked ? 'Locked' : 'Unlocked' }}
                                </span>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold ring-1 {{ $user->hasVerifiedEmail() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
                                    {{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                            <a href="{{ route('admin.users') }}"
                                class="btn-smooth inline-flex items-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80 sm:text-sm">
                                <i class="bi bi-arrow-left"></i>
                                Back to Users
                            </a>
                        </div>
                    </div>
                </div>
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

            @if ($errors->any())
                <div
                    class="glass-panel !rounded-2xl border border-rose-200/70 bg-rose-50/70 px-4 py-4 text-sm text-rose-700 shadow-sm backdrop-blur">
                    <div class="flex items-start gap-3">
                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-rose-800">Please fix the following errors</p>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid gap-5 lg:grid-cols-[1fr_280px]">
                <form method="POST" action="{{ route('admin.user.update', $user->id) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                        <div class="border-b border-white/60 px-4 py-4 sm:px-6">
                            <h2 class="text-sm font-semibold text-slate-900">Account details</h2>
                            <p class="text-xs text-slate-500">Update profile information and assigned access role.</p>
                        </div>

                        <div class="space-y-6 p-4 sm:p-6">
                            <div>
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-100/80 text-indigo-600">
                                        <i class="bi bi-person text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-slate-800">Personal information</h3>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div
                                        class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                        <label for="first_name" class="{{ $labelClass }}">First name</label>
                                        <input id="first_name" type="text" name="first_name"
                                            value="{{ old('first_name', $user->first_name) }}" required
                                            data-title-case class="{{ $fieldClass }}">
                                    </div>

                                    <div
                                        class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                        <label for="last_name" class="{{ $labelClass }}">Last name</label>
                                        <input id="last_name" type="text" name="last_name"
                                            value="{{ old('last_name', $user->last_name) }}" required
                                            data-title-case class="{{ $fieldClass }}">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-100/80 text-cyan-600">
                                        <i class="bi bi-envelope text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-slate-800">Contact</h3>
                                </div>

                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div
                                        class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                        <label for="email" class="{{ $labelClass }}">Email</label>
                                            @if ($emailChangeLocked)
                                                <input type="hidden" name="email" value="{{ $user->email }}">
                                                <input id="email" type="email" value="{{ $user->email }}" disabled
                                                    class="{{ $fieldClass }} opacity-70">
                                                <p class="mt-1.5 text-[11px] font-medium text-amber-700">
                                                    Email is locked because this is the last active admin account.
                                                    Changing it would reset verification and lock you out.
                                                </p>
                                            @else
                                                <input id="email" type="email" name="email"
                                                    value="{{ old('email', $user->email) }}" required
                                                    class="{{ $fieldClass }}">
                                                <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                                    Changing email resets verification.
                                                </p>
                                            @endif
                                    </div>

                                    <div
                                        class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                        <label for="contact_number" class="{{ $labelClass }}">Contact number</label>
                                        <input id="contact_number" type="text" name="contact_number"
                                            value="{{ old('contact_number', $user->contact_number) }}" required
                                            class="{{ $fieldClass }}">
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="mb-3 flex items-center gap-2">
                                    <span
                                        class="flex h-7 w-7 items-center justify-center rounded-lg bg-violet-100/80 text-violet-600">
                                        <i class="bi bi-shield-check text-sm"></i>
                                    </span>
                                    <h3 class="text-sm font-semibold text-slate-800">Access role</h3>
                                </div>

                                <div
                                    class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                    <label for="role_id" class="{{ $labelClass }}">User role</label>
                                    @if ($roleChangeLocked)
                                        <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                                        <select id="role_id" disabled class="{{ $fieldClass }} opacity-70">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    @selected(old('role_id', $user->role_id) == $role->id)>
                                                    {{ $role->name_en }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1.5 text-[11px] font-medium text-amber-700">
                                            @if ($isSelf)
                                                You cannot change your own admin role.
                                            @else
                                                Role is locked because this is the last active admin account.
                                            @endif
                                        </p>
                                    @else
                                        <select id="role_id" name="role_id" required class="{{ $fieldClass }}">
                                            @foreach ($roles as $role)
                                                <option value="{{ $role->id }}"
                                                    @selected(old('role_id', $user->role_id) == $role->id)>
                                                    {{ $role->name_en }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                            Changing the role signs the user out immediately and updates access.
                                        </p>
                                    @endif
                                </div>

                                @if (! $roleChangeLocked && $organizerAssets > 0)
                                    <div
                                        class="mt-4 btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm">
                                        <label for="reassign_organizer_id" class="{{ $labelClass }}">Reassign organizer records</label>
                                        @if ($otherOrganizers->isEmpty())
                                            <p class="text-[11px] font-medium text-amber-700">
                                                This user owns {{ $organizerAssets }} event/host/artist record(s). Create another active organizer before changing this role.
                                            </p>
                                        @else
                                            <select id="reassign_organizer_id" name="reassign_organizer_id"
                                                class="{{ $fieldClass }}">
                                                <option value="">Select organizer</option>
                                                @foreach ($otherOrganizers as $organizer)
                                                    <option value="{{ $organizer->id }}"
                                                        @selected((int) old('reassign_organizer_id') === $organizer->id)>
                                                        {{ $organizer->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                                Required when moving this user off the organizer role. {{ $organizerAssets }} record(s) will transfer.
                                            </p>
                                            @error('reassign_organizer_id')
                                                <p class="mt-1.5 text-[11px] font-medium text-rose-700">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>
                                @endif

                                @if (! $roleChangeLocked && $croAssets > 0)
                                    <div
                                        class="mt-4 btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm">
                                        <label for="reassign_cro_id" class="{{ $labelClass }}">Reassign CRO records</label>
                                        @if ($otherCros->isEmpty())
                                            <p class="text-[11px] font-medium text-amber-700">
                                                This user is assigned to {{ $croAssets }} event/complaint/inquiry record(s). Create another active CRO before changing this role.
                                            </p>
                                        @else
                                            <select id="reassign_cro_id" name="reassign_cro_id" class="{{ $fieldClass }}">
                                                <option value="">Select CRO</option>
                                                @foreach ($otherCros as $cro)
                                                    <option value="{{ $cro->id }}"
                                                        @selected((int) old('reassign_cro_id') === $cro->id)>
                                                        {{ $cro->full_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                                Required when moving this user off the CRO role. {{ $croAssets }} record(s) will transfer.
                                            </p>
                                            @error('reassign_cro_id')
                                                <p class="mt-1.5 text-[11px] font-medium text-rose-700">{{ $message }}</p>
                                            @enderror
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div
                            class="flex flex-col-reverse gap-2 border-t border-white/60 bg-white/30 px-4 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                            <a href="{{ route('admin.users') }}"
                                class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-lg border border-white/70 bg-white/50 px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm backdrop-blur hover:border-indigo-200 hover:bg-white/80">
                                Cancel
                            </a>
                            <button type="submit"
                                class="btn-smooth inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 hover:shadow-md">
                                <i class="bi bi-check2-circle"></i>
                                Update User
                            </button>
                        </div>
                    </section>
                </form>

                <aside class="space-y-4">
                    <div class="glass-card !rounded-2xl p-4 sm:p-5">
                        <h4 class="text-xs font-semibold uppercase tracking-wide text-slate-500">User summary</h4>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">User ID</dt>
                                <dd class="font-semibold text-slate-900">#{{ $user->id }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">Role</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ $user->userRole?->name_en ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">Joined</dt>
                                <dd class="font-semibold text-slate-900">{{ $user->created_at?->format('M d, Y') }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="font-semibold text-slate-900">
                                    {{ $user->hasVerifiedEmail() ? 'Verified' : 'Unverified' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    @unless ($user->hasVerifiedEmail())
                        <div class="glass-card !rounded-2xl border border-amber-200/70 bg-amber-50/60 p-4 sm:p-5">
                            <h4 class="text-sm font-semibold text-amber-950">Email verification</h4>
                            <p class="mt-1 text-xs leading-5 text-amber-800">
                                This account cannot access verified routes until the email is verified.
                            </p>
                            <div class="mt-4 flex flex-col gap-2">
                                <form method="POST" action="{{ route('admin.user.resendVerification', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg border border-amber-200 bg-white/80 px-3 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-100">
                                        <i class="bi bi-envelope"></i>
                                        Resend verification
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('admin.user.markVerified', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Mark {{ $user->email }} as verified without the user confirming?')"
                                        class="btn-smooth inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700">
                                        <i class="bi bi-patch-check"></i>
                                        Mark as verified
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endunless

                    <div class="glass-card !rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 sm:p-5">
                        <div class="flex gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                                <i class="bi bi-info-circle"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-indigo-950">Review before updating</h4>
                                <p class="mt-1 text-xs leading-5 text-indigo-800">
                                    Changing the email resets verification. Changing the role can immediately affect
                                    access permissions.
                                </p>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
