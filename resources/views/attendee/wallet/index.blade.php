<x-app-layout>

    <x-slot name="header">
        <div>
            <h2 class="text-3xl font-bold text-slate-900">{{ t(['en' => 'My Wallet', 'si' => 'මගේ පසුම්බිය']) }}</h2>
            <p class="mt-1 text-slate-500">{{ t(['en' => 'Use your balance to buy tickets or top up with card.', 'si' => 'ටිකට් මිලදී ගැනීමට ඔබේ ශේෂය භාවිතා කරන්න හෝ කාඩ්පතෙන් ගෙවීම කරන්න.']) }}</p>
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
                <p class="text-sm uppercase tracking-wider text-indigo-100">{{ t(['en' => 'Available Balance', 'si' => 'ලබා ගත හැකි ශේෂය']) }}</p>
                <h3 class="mt-2 text-5xl font-black">Rs {{ number_format((float) $wallet->balance, 2) }}</h3>
                <p class="mt-3 text-indigo-100 text-sm">{{ t(['en' => 'Use at checkout via "Pay by Wallet" or top up below.', 'si' => 'ගෙවීමේදී "පසුම්බියෙන් ගෙවන්න" භාවිතා කරන්න හෝ පහතින් ගෙවීම කරන්න.']) }}</p>
            </div>

            <div class="grid gap-8 lg:grid-cols-2">
                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">{{ t(['en' => 'Top-up Wallet', 'si' => 'පසුම්බියට ගෙවීම කරන්න']) }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ t(['en' => 'Add funds securely using Stripe card payment.', 'si' => 'Stripe කාඩ්පත් ගෙවීම භාවිතයෙන් ආරක්ෂිතව මුදල් එකතු කරන්න.']) }}</p>

                    <form action="{{ route('attendee.wallet.topup') }}" method="POST" class="mt-6 space-y-4">
                        @csrf
                        <div>
                            <label for="amount" class="block text-sm font-semibold text-slate-700">{{ t(['en' => 'Amount (Rs)', 'si' => 'මුදල (රු)']) }}</label>
                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                min="100"
                                max="500000"
                                step="0.01"
                                required
                                placeholder="{{ t(['en' => 'e.g. 5000', 'si' => 'උදා: 5000']) }}"
                                class="mt-2 w-full rounded-2xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                        </div>
                        <button type="submit" class="w-full rounded-2xl bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ t(['en' => 'Top-up with Card', 'si' => 'කාඩ්පතෙන් ගෙවීම කරන්න']) }}
                        </button>
                    </form>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">{{ t(['en' => 'Pay by Wallet', 'si' => 'පසුම්බියෙන් ගෙවන්න']) }}</h3>
                    <p class="mt-2 text-sm text-slate-500">
                        {{ t(['en' => 'When checking out from your cart, select "Pay by Wallet" if your balance covers the total.', 'si' => 'කාර්ට් හි දී ගෙවීමේදී, ඔබේ ශේෂය මුළු මුදලට ප්‍රමාණවත් නම් "පසුම්බියෙන් ගෙවන්න" තෝරන්න.']) }}
                    </p>
                    <a href="{{ route('attendee.cart.index') }}" class="mt-6 inline-flex w-full items-center justify-center rounded-2xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        {{ t(['en' => 'Go to Cart', 'si' => 'කාර්ට් එකට යන්න']) }}
                    </a>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-6 py-4">
                    <h3 class="text-lg font-bold text-slate-900">{{ t(['en' => 'Recent Transactions', 'si' => 'මෑත ගනුදෙනු']) }}</h3>
                </div>

                @if($wallet->transactions->isEmpty())
                    <div class="p-8 text-center text-slate-500">{{ t(['en' => 'No transactions yet.', 'si' => 'තවම ගනුදෙනු නැත.']) }}</div>
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
                                    <p class="text-xs text-slate-500">{{ t(['en' => 'Bal:', 'si' => 'ශේෂය:']) }} Rs {{ number_format((float) $transaction->balance_after, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>
    </div>

</x-app-layout>
