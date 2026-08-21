<!-- D:\projects\CeyonX\EventHub\database\migrations\2026_08_20_211000_add_discount_fields_to_ticket_categories_table.php -->

<!-- TicketCategory.php file 

line 123=
    /**
     * Whether a timed discount is configured and currently active.
     */
    public function hasActiveDiscount(?\DateTimeInterface $at = null): bool
    {
        if ($this->discount_price === null) {
            return false;
        }

        $discountPrice = (float) $this->discount_price;
        $basePrice = (float) $this->ticket_price;

        if ($discountPrice < 0 || $discountPrice >= $basePrice) {
            return false;
        }

        if (! $this->discount_start || ! $this->discount_end) {
            return false;
        }

        $moment = $at ? \Carbon\Carbon::parse($at) : now();

        return $moment->betweenIncluded($this->discount_start, $this->discount_end);
    }

    /**
     * Price charged at checkout (discounted when the window is active).
     */
    public function effectivePrice(?\DateTimeInterface $at = null): float
    {
        if ($this->hasActiveDiscount($at)) {
            return round((float) $this->discount_price, 2);
        }

        return round((float) $this->ticket_price, 2);
    }

   *cartitem.php file 

    public function getUnitPriceAttribute(): float
    {
        return $this->ticketCategory->effectivePrice();
    }

    *D:\projects\CeyonX\EventHub\app\Services\StripeCheckoutService.php

    public function createCheckoutSession($cartItems, int $userId): Session
    {
        $lineItems = [];
        $totalAmount = 0;

        foreach ($cartItems as $cartItem) {
            $unitPrice = $cartItem->ticketCategory->effectivePrice();
            $totalAmount += $unitPrice * $cartItem->quantity;


    line 102 replace

    line 212 replace

    line 320 replace

    *D:\projects\CeyonX\EventHub\app\Http\Controllers\TicketCategoryController.php
    line 55,60,61,68-70,88,93,94,160-162,167,174-176,187-189,212-214,327-329

    *D:\projects\CeyonX\EventHub\resources\views\organizer\ticket-categories\create.blade.php

    *D:\projects\CeyonX\EventHub\resources\views\attendee\show.blade.php
    line 703 ={{--@if ($category->hasActiveDiscount())
                                                        <p class="text-sm text-slate-400 line-through">Rs {{ number_format($category->ticket_price) }}</p>
                                                        <p class="text-2xl font-bold text-indigo-600">Rs {{ number_format($category->effectivePrice()) }}</p>
                                                        <p class="text-xs font-semibold text-amber-700">{{ t(['en' => 'Limited-time discount', 'si' => 'සීමිත කාල වට්ටම']) }}</p>
                                                    @else --}}
                                                        <p class="text-2xl font-bold text-indigo-600">Rs {{ number_format($category->ticket_price) }}</p>
                                                    @endif
    line 722= @click="selected = { id: {{ $category->id }}, name: {{ json_encode($category->name) }}, price: {{ $category->effectivePrice() }}, available: {{ $available }}, color: {{ json_encode($category->ticket_color) }} }; qty = 1; amount = (selected.price * 1).toFixed(2); showModal = true"

    *D:\projects\CeyonX\EventHub\resources\views\organizer\ticket-categories\create.blade.php

    line 59,60 replace = <label for="ticket_price" class="block text-sm font-medium text-gray-700">Ticket Price (LKR)</label>
                            <input type="number" step="1" name="ticket_price" id="ticket_price" min="0"
                                value="{{ old('ticket_price') }}"

    line 63 ={{-- Timed discount (optional) replace --}}
                        <div class="rounded-md border border-amber-200 bg-amber-50/60 p-4 space-y-4">
                            <div>
                                <p class="text-sm font-semibold text-amber-900">Timed discount (optional)</p>
                                <p class="mt-1 text-xs text-amber-800">
                                    During the discount period, attendees pay the discount price instead of the full ticket price.
                                </p>
                            </div>
                            <div>
                                <label for="discount_price" class="block text-sm font-medium text-gray-700">Discount Price (LKR)</label>
                                <input type="number" step="1" name="discount_price" id="discount_price" min="0"
                                    value="{{ old('discount_price') }}"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="discount_start" class="block text-sm font-medium text-gray-700">Discount Start</label>
                                    <input type="datetime-local" name="discount_start" id="discount_start"
                                        value="{{ old('discount_start') }}"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                                <div>
                                    <label for="discount_end" class="block text-sm font-medium text-gray-700">Discount End</label>
                                    <input type="datetime-local" name="discount_end" id="discount_end"
                                        value="{{ old('discount_end') }}"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                                </div>
                            </div>
                        </div>

    *D:\projects\CeyonX\EventHub\resources\views\organizer\ticket-categories\edit.blade.php

    line 73 replace=LKR {{ number_format($ticketCategory->effectivePrice()) }}
                                @if ($ticketCategory->hasActiveDiscount())
                                    · {{ __('discount') }}
                                @endif

    add 159= <div
                                    class="rounded-2xl border border-amber-100 bg-amber-50/70 p-4 transition focus-within:border-amber-200 focus-within:bg-white focus-within:shadow-sm md:col-span-2">
                                    <p class="text-sm font-semibold text-amber-900">{{ __('Timed discount (optional)') }}</p>
                                    <p class="mt-1 text-xs text-amber-800">
                                        {{ __('During this period, attendees pay the discount price. Leave blank to remove.') }}
                                    </p>
                                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div>
                                            <x-input-label for="discount_price" :value="__('Discount Price (LKR)')" />
                                            <x-text-input id="discount_price"
                                                class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="number" name="discount_price" min="0" step="1"
                                                :value="old('discount_price', $ticketCategory->discount_price)" />
                                            <x-input-error :messages="$errors->get('discount_price')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="discount_start" :value="__('Discount Start')" />
                                            <x-text-input id="discount_start"
                                                class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="datetime-local" name="discount_start"
                                                :value="old('discount_start', optional($ticketCategory->discount_start)->format('Y-m-d\TH:i'))" />
                                            <x-input-error :messages="$errors->get('discount_start')" class="mt-2" />
                                        </div>
                                        <div>
                                            <x-input-label for="discount_end" :value="__('Discount End')" />
                                            <x-text-input id="discount_end"
                                                class="mt-2 block w-full rounded-xl border-gray-200 bg-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                type="datetime-local" name="discount_end"
                                                :value="old('discount_end', optional($ticketCategory->discount_end)->format('Y-m-d\TH:i'))" />
                                            <x-input-error :messages="$errors->get('discount_end')" class="mt-2" />
                                        </div>
                                    </div>
                                    @if ($ticketCategory->hasActiveDiscount())
                                        <p class="mt-3 text-xs font-medium text-emerald-700">
                                            {{ __('Discount is active now — effective price LKR :price', [
                                                'price' => number_format($ticketCategory->effectivePrice()),
                                            ]) }}
                                        </p>
                                    @endif
                                </div>

    add 268= @if ($ticketCategory->discount_price !== null)
                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Discount price') }}</dt>
                                        <dd class="font-semibold text-amber-700">LKR {{ number_format($ticketCategory->discount_price) }}</dd>
                                    </div>
                                    <div class="flex items-start justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Discount window') }}</dt>
                                        <dd class="text-right text-xs font-medium text-gray-700">
                                            {{ optional($ticketCategory->discount_start)->format('d M Y H:i') ?? '—' }}
                                            <br>→ {{ optional($ticketCategory->discount_end)->format('d M Y H:i') ?? '—' }}
                                        </dd>
                                    </div>
                                    <div class="flex items-center justify-between gap-4">
                                        <dt class="text-gray-500">{{ __('Effective now') }}</dt>
                                        <dd class="font-semibold {{ $ticketCategory->hasActiveDiscount() ? 'text-emerald-700' : 'text-gray-900' }}">
                                            LKR {{ number_format($ticketCategory->effectivePrice()) }}
                                        </dd>
                                    </div>
                                @endif

    D:\projects\CeyonX\EventHub\resources\views\organizer\events\show.blade.php

    replace 761= @if ($category->hasActiveDiscount())
                                                    <div class="text-xs font-normal text-gray-400 line-through">LKR {{ number_format($category->ticket_price, 0) }}</div>
                                                    <div class="text-amber-700">LKR {{ number_format($category->effectivePrice(), 0) }}</div>
                                                    <div class="text-[10px] font-semibold uppercase tracking-wide text-amber-600">{{ __('Discount') }}</div>
                                                @elseif ($category->discount_price !== null)
                                                    <div>LKR {{ number_format($category->ticket_price, 0) }}</div>
                                                    <div class="text-[10px] font-normal text-gray-500">
                                                        {{ __('Promo LKR :price', ['price' => number_format($category->discount_price, 0)]) }}
                                                    </div>
                                                @else
                                                    LKR {{ number_format($category->ticket_price, 0) }}
                                                @endif
    
    
    --}}