<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-3xl font-bold text-slate-900">
                    Create Employee
                </h2>
                <p class="text-sm text-slate-500 mt-1">
                    Add a new staff account and assign system access.
                </p>
            </div>

            <a href="{{ route('admin.users') }}"
                class="inline-flex items-center px-5 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 font-medium hover:bg-slate-50 transition">
                ← Back to Users
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            {{-- Error Message --}}
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
                    <div class="flex items-start gap-3">
                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                            !
                        </div>

                        <div>
                            <p class="text-red-800 font-semibold">
                                Please fix the following errors:
                            </p>

                            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Main Form Card --}}
            <form method="POST" action="{{ route('admin.employee.store') }}">
                @csrf

                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">

                    {{-- Header --}}
                    <div class="px-8 py-6 border-b border-slate-100">
                        <h3 class="text-xl font-semibold text-slate-900">
                            Employee Details
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            Fill in the required information below.
                        </p>
                    </div>

                    <div class="p-8 space-y-8">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- First Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    First Name
                                </label>

                                <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                    placeholder="Enter first name" data-title-case
                                    class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            {{-- Last Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Last Name
                                </label>

                                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                    placeholder="Enter last name" data-title-case
                                    class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- NIC --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                NIC
                            </label>

                            <input type="text" name="nic" value="{{ old('nic') }}" required
                                placeholder="Enter NIC number"
                                class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                            <p class="mt-2 text-xs text-slate-500">
                                This must be unique for each employee account.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- Email --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Email
                                </label>

                                <input type="email" name="email" value="{{ old('email') }}" required
                                    placeholder="employee@example.com"
                                    class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            {{-- Contact Number --}}
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Contact Number
                                </label>

                                <input type="text" name="contact_number" value="{{ old('contact_number') }}" required
                                    placeholder="Enter contact number"
                                    class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        {{-- User Role --}}
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                User Role
                            </label>

                            <select name="role_id" required
                                class="w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Role --</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ old('role_id') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name_en }}
                                    </option>
                                @endforeach
                            </select>

                            <p class="mt-2 text-xs text-slate-500">
                                The selected role controls what this employee can access.
                            </p>
                        </div>
                    </div>

                    {{-- Footer Actions --}}
                    <div class="border-t border-slate-100 bg-slate-50 px-8 py-5">
                        <div class="flex flex-col sm:flex-row justify-end gap-3">
                            <a href="{{ route('admin.users') }}"
                                class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 font-medium hover:bg-slate-100 transition text-center">
                                Cancel
                            </a>

                            <button type="submit"
                                class="px-6 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold shadow hover:bg-indigo-700 transition">
                                Save Employee
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
