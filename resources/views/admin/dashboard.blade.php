<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-900">{{ __('Admin Dashboard') }}</h2>
                <p class="mt-1 text-sm text-gray-500">Quick overview of system activity and user management.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.users') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-full text-sm font-semibold shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition">{{ __('All Users') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-6 lg:grid-cols-3 mb-6">
                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Total Users</p>
                    <h3 class="mt-4 text-3xl font-semibold text-slate-900">1,254</h3>
                    <p class="mt-2 text-sm text-slate-500">Active accounts this month</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Pending Verifications</p>
                    <h3 class="mt-4 text-3xl font-semibold text-slate-900">28</h3>
                    <p class="mt-2 text-sm text-slate-500">Emails waiting for confirmation</p>
                </div>
                <div class="rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-sm">
                    <p class="text-sm font-medium text-slate-500">Locked Accounts</p>
                    <h3 class="mt-4 text-3xl font-semibold text-slate-900">7</h3>
                    <p class="mt-2 text-sm text-slate-500">Accounts locked by security policy</p>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-200 sm:px-8">
                        <h3 class="text-lg font-semibold text-slate-900">Recent activity</h3>
                        <p class="mt-1 text-sm text-slate-500">Recent user actions and system notifications.</p>
                    </div>
                    <div class="divide-y divide-slate-200 px-6 py-6 sm:px-8">
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">New event manager account created</p>
                                <p class="text-sm text-slate-500">2 minutes ago</p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">User</span>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">5 users verified their email</p>
                                <p class="text-sm text-slate-500">14 minutes ago</p>
                            </div>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-emerald-700">Verified</span>
                        </div>
                        <div class="flex flex-col gap-1 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-slate-900">Locked 2 accounts after failed login attempts</p>
                                <p class="text-sm text-slate-500">37 minutes ago</p>
                            </div>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-rose-700">Security</span>
                        </div>
                    </div>
                </div>

                <aside class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-200 sm:px-8">
                        <h3 class="text-lg font-semibold text-slate-900">Admin actions</h3>
                        <p class="mt-1 text-sm text-slate-500">Quick access to management tools.</p>
                    </div>
                    <div class="space-y-3 px-6 py-6 sm:px-8">
                        <a href="{{ route('admin.users') }}" class="block rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-100">Manage users</a>
                        <button class="w-full rounded-2xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">View reports</button>
                        <button class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-900 hover:bg-slate-50">Review verifications</button>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
