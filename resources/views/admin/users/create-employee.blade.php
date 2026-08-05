<x-app-layout>
    @php
        $fieldClass = 'w-full rounded-lg border border-white/70 bg-white/60 px-3 py-2.5 text-sm text-slate-700 shadow-sm backdrop-blur-md placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500';
        $labelClass = 'mb-1.5 block text-xs font-semibold text-slate-600';
    @endphp

    <div class="admin-create-employee relative isolate overflow-hidden py-5 sm:py-6">
        <div class="pointer-events-none absolute inset-0 -z-10">
            <div class="absolute inset-0 bg-gradient-to-br from-slate-100 via-indigo-50/40 to-cyan-50/50"></div>
            <div class="absolute -left-24 top-10 h-72 w-72 rounded-full bg-indigo-300/25 blur-3xl"></div>
            <div class="absolute right-0 top-40 h-80 w-80 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-20 left-1/3 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"></div>
            <div class="absolute inset-0 bg-grid-slate-100 opacity-60"></div>
        </div>

        <div class="mx-auto max-w-4xl space-y-5 px-4 sm:px-6 lg:px-8">

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
                                    <i class="bi bi-person-plus text-sm sm:text-base"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-700">Access management</p>
                                    <h1 class="truncate text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                        Create Employee
                                    </h1>
                                </div>
                            </div>
                            <p class="mt-1.5 text-sm text-slate-500">
                                Add a staff account and assign platform access ·
                                {{ now()->format('l, M j, Y') }}
                            </p>
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

            {{-- Context tips --}}
            <section class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="glass-card kpi-lift group border-t-4 border-t-indigo-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Profile</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Identity details</p>
                            <p class="mt-1 text-xs text-slate-500">Name, NIC, and contact info</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100/70 text-indigo-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-person-vcard"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card kpi-lift group border-t-4 border-t-cyan-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Access</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Role assignment</p>
                            <p class="mt-1 text-xs text-slate-500">Controls module permissions</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-100/70 text-cyan-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                    </div>
                </div>

                <div class="glass-card kpi-lift group border-t-4 border-t-emerald-500 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Security</p>
                            <p class="mt-1 text-sm font-semibold text-slate-800">Unique NIC</p>
                            <p class="mt-1 text-xs text-slate-500">Must be unique per account</p>
                        </div>
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100/70 text-emerald-600 transition-transform duration-300 group-hover:scale-110">
                            <i class="bi bi-fingerprint"></i>
                        </div>
                    </div>
                </div>
            </section>

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

            {{-- Form --}}
            <form method="POST" action="{{ route('admin.employee.store') }}" class="space-y-5">
                @csrf

                <section class="glass-card overflow-hidden !p-0 !rounded-2xl">
                    <div class="border-b border-white/60 px-4 py-4 sm:px-6">
                        <h2 class="text-sm font-semibold text-slate-900">Employee details</h2>
                        <p class="text-xs text-slate-500">Fill in the required information below.</p>
                    </div>

                    <div class="space-y-6 p-4 sm:p-6">
                        {{-- Personal --}}
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
                                        value="{{ old('first_name') }}" required placeholder="Enter first name"
                                        data-title-case class="{{ $fieldClass }}">
                                </div>

                                <div
                                    class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                    <label for="last_name" class="{{ $labelClass }}">Last name</label>
                                    <input id="last_name" type="text" name="last_name"
                                        value="{{ old('last_name') }}" required placeholder="Enter last name"
                                        data-title-case class="{{ $fieldClass }}">
                                </div>
                            </div>
                        </div>

                        {{-- Identity --}}
                        <div>
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="flex h-7 w-7 items-center justify-center rounded-lg bg-cyan-100/80 text-cyan-600">
                                    <i class="bi bi-card-heading text-sm"></i>
                                </span>
                                <h3 class="text-sm font-semibold text-slate-800">Identity & contact</h3>
                            </div>

                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div
                                    class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80 md:col-span-2">
                                    <label for="nic" class="{{ $labelClass }}">NIC</label>
                                    <input id="nic" type="text" name="nic" value="{{ old('nic') }}"
                                        required placeholder="Enter NIC number" class="{{ $fieldClass }}">
                                    <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                        Must be unique for each employee account.
                                    </p>
                                </div>

                                <div
                                    class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                    <label for="email" class="{{ $labelClass }}">Email</label>
                                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                                        required placeholder="employee@example.com" class="{{ $fieldClass }}">
                                </div>

                                <div
                                    class="btn-smooth rounded-xl border border-white/60 bg-white/40 p-3 backdrop-blur-sm hover:-translate-y-0.5 hover:bg-white/70 hover:shadow-sm focus-within:border-indigo-200 focus-within:bg-white/80">
                                    <label for="contact_number" class="{{ $labelClass }}">Contact number</label>
                                    <input id="contact_number" type="text" name="contact_number"
                                        value="{{ old('contact_number') }}" required
                                        placeholder="Enter contact number" class="{{ $fieldClass }}">
                                </div>
                            </div>
                        </div>

                        {{-- Role --}}
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
                                <select id="role_id" name="role_id" required class="{{ $fieldClass }}">
                                    <option value="">Select role</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                            {{ $role->name_en }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1.5 text-[11px] font-medium text-slate-500">
                                    The selected role controls what this employee can access.
                                </p>
                            </div>
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
                            Save Employee
                        </button>
                    </div>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
