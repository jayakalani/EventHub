<x-app-layout>

    <x-slot name="header">
        <div class="flex flex-col gap-0.5 sm:flex-row sm:items-baseline sm:justify-between sm:gap-4">
            <h2 class="text-lg font-bold leading-tight text-slate-900 sm:text-xl">{{ t(['en' => 'My Wallet', 'si' => 'මගේ පසුම්බිය']) }}</h2>
            <p class="text-xs text-slate-500 sm:text-sm sm:text-right">{{ t(['en' => 'Use your balance to buy tickets or top up with card.', 'si' => 'ටිකට් මිලදී ගැනීමට ඔබේ ශේෂය භාවිතා කරන්න හෝ කාඩ්පතෙන් ගෙවීම කරන්න.']) }}</p>
        </div>
    </x-slot>

    <div class="py-5">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 space-y-4">

            @if(session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div
                class="rounded-2xl px-5 py-4 text-white shadow-lg sm:px-6 sm:py-5"
                style="background: linear-gradient(115deg, #02031F 0%, #030638 25%, #070130 50%, #0F0363 75%, #2A1585 100%);">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-violet-100/90">{{ t(['en' => 'Available Balance', 'si' => 'ලබා ගත හැකි ශේෂය']) }}</p>
                <h3 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">Rs {{ number_format((float) $wallet->balance, 2) }}</h3>
                <p class="mt-1.5 text-sm text-violet-100/90">{{ t(['en' => 'Use at checkout via "Pay by Wallet" or top up below.', 'si' => 'ගෙවීමේදී "පසුම්බියෙන් ගෙවන්න" භාවිතා කරන්න හෝ පහතින් ගෙවීම කරන්න.']) }}</p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
                    x-data="{
                        amount: @js(old('amount', '')),
                        presets: [1000, 5000, 10000],
                        selectPreset(value) {
                            this.amount = value;
                        },
                        isPreset(value) {
                            return Number(this.amount) === value;
                        },
                    }"
                >
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ t(['en' => 'Top-up Wallet', 'si' => 'පසුම්බියට ගෙවීම කරන්න']) }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ t(['en' => 'Add funds securely using Stripe card payment.', 'si' => 'Stripe කාඩ්පත් ගෙවීම භාවිතයෙන් ආරක්ෂිතව මුදල් එකතු කරන්න.']) }}</p>

                    <form action="{{ route('attendee.wallet.topup') }}" method="POST" class="mt-4 space-y-3">
                        @csrf
                        <div>
                            <div class="flex items-baseline justify-between gap-2">
                                <label for="amount" class="block text-sm font-semibold text-slate-700">{{ t(['en' => 'Amount (Rs)', 'si' => 'මුදල (රු)']) }}</label>
                                <p class="text-xs text-slate-500">
                                    {{ t(['en' => 'Min Rs 100 · Max Rs 500,000', 'si' => 'අවම රු 100 · උපරිම රු 500,000']) }}
                                </p>
                            </div>

                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <template x-for="preset in presets" :key="preset">
                                    <button
                                        type="button"
                                        @click="selectPreset(preset)"
                                        :class="isPreset(preset)
                                            ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                            : 'border-slate-200 bg-slate-50 text-slate-700 hover:border-primary/40 hover:bg-primary/5'"
                                        class="rounded-xl border px-2 py-2 text-sm font-semibold transition"
                                        x-text="'Rs ' + preset.toLocaleString()"
                                    ></button>
                                </template>
                            </div>

                            <input
                                type="number"
                                id="amount"
                                name="amount"
                                x-model="amount"
                                min="100"
                                max="500000"
                                step="0.01"
                                required
                                placeholder="{{ t(['en' => 'Or enter a custom amount', 'si' => 'නැතහොත් අභිරුචි මුදලක් ඇතුළත් කරන්න']) }}"
                                class="mt-2.5 w-full rounded-xl border-slate-300 py-2 text-sm shadow-sm focus:border-primary focus:ring-primary"
                            >
                            <p class="mt-1.5 text-xs text-slate-500">
                                {{ t(['en' => 'Choose a quick amount or type any value between Rs 100 and Rs 500,000.', 'si' => 'ඉක්මන් මුදලක් තෝරන්න හෝ රු 100 සහ රු 500,000 අතර අගයක් ටයිප් කරන්න.']) }}
                            </p>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                            {{ t(['en' => 'Top-up with Card', 'si' => 'කාඩ්පතෙන් ගෙවීම කරන්න']) }}
                        </button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                    <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ t(['en' => 'Pay by Wallet', 'si' => 'පසුම්බියෙන් ගෙවන්න']) }}</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ t(['en' => 'When checking out from your cart, select "Pay by Wallet" if your balance covers the total.', 'si' => 'කාර්ට් හි දී ගෙවීමේදී, ඔබේ ශේෂය මුළු මුදලට ප්‍රමාණවත් නම් "පසුම්බියෙන් ගෙවන්න" තෝරන්න.']) }}
                    </p>
                    <a href="{{ route('attendee.cart.index') }}" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark">
                        {{ t(['en' => 'Go to Cart', 'si' => 'කාර්ට් එකට යන්න']) }}
                    </a>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-4 py-3 sm:px-5">
                    <h3 class="text-base font-bold text-slate-900">{{ t(['en' => 'Recent Transactions', 'si' => 'මෑත ගනුදෙනු']) }}</h3>
                </div>

                @if($wallet->transactions->isEmpty())
                    <div class="px-4 py-6 text-center text-sm text-slate-500">{{ t(['en' => 'No transactions yet.', 'si' => 'තවම ගනුදෙනු නැත.']) }}</div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($wallet->transactions as $transaction)
                            <div class="px-4 py-3 sm:px-5 flex items-center justify-between text-sm">
                                <div class="min-w-0 pr-3">
                                    <p class="font-medium text-slate-800 truncate">{{ $transaction->description }}</p>
                                    <p class="text-xs text-slate-500">{{ $transaction->created_at->format('d M Y, H:i') }}</p>
                                </div>
                                <div class="text-right shrink-0">
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
