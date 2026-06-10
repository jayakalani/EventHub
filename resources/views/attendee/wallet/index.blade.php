<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">My Wallet</h2>
            <p class="mt-1 text-slate-500">Use your balance to buy tickets or top up with card.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-6 space-y-8">

            @if(session('success'))
                <div class="rounded-2xl border border-green-200 bg-green-50 px-5 py-4 text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="rounded-3xl bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 p-8 text-white shadow-xl">
                <p class="text-sm uppercase tracking-wider text-indigo-100">Available Balance</p>
                <h3 class="mt-2 text-5xl font-black">Rs {{ number_format((float) $wallet->balance, 2) }}</h3>
                <p class="mt-3 text-indigo-100 text-sm">Use at checkout via "Pay by Wallet" or top up below.</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">Top-up Wallet</h3>
                    <p class="mt-2 text-sm text-slate-500">Add funds securely using Stripe card payment.</p>

                    <form action="{{ route('attendee.wallet.topup') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="amount" class="block text-sm font-semibold text-slate-700">Amount (Rs)</label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="100"
                                max="500000"
                                step="0.01"
                                required
                                placeholder="e.g. 5000"
                                class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            Top-up with Card
                        </button>
                    </form>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">Pay by Wallet</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        When checking out from your cart, select "Pay by Wallet" if your balance covers the total.
                    </p>
                    <a href="{{ route('attendee.cart.index') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Go to Cart
                    </a>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                </div>

                @if($wallet->transactions->isEmpty())
                    <div class="p-8 text-center text-slate-500">No transactions yet.</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($wallet->transactions as $transaction)
                            <div class="px-6 py-4 flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium text-slate-800">{{ $transaction->description }}</p>
                                    <p class="text-xs text-slate-500">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="font-bold {{ $transaction->type->value === 'credit' ? 'text-emerald-600' : 'text-red-600' }}">
                                        {{ $transaction->type->value === 'credit' ? '+' : '-' }}Rs {{ number_format((float) $transaction->amount, 2) }}
                                    </p>
                                    <p class="text-xs text-slate-500">Bal: Rs {{ number_format((float) $transaction->balance_after, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-app-layout>
