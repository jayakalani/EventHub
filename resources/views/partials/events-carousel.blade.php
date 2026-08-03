@php
    $bannerEvents = $carouselEvents
        ->filter(fn ($event) => filled($event->cover))
        ->values();

    if ($bannerEvents->isEmpty()) {
        $bannerEvents = $carouselEvents->values();
    }

    $bannerCount = $bannerEvents->count();
@endphp

@if ($bannerCount > 0)
    <section
        class="mx-auto max-w-7xl px-4 pt-8 pb-2 sm:px-6 lg:px-8"
        x-data="{
            index: 0,
            perPage: 5,
            total: {{ $bannerCount }},
            timer: null,
            init() {
                this.updatePerPage();
                window.addEventListener('resize', () => this.updatePerPage());
                this.start();
            },
            updatePerPage() {
                const w = window.innerWidth;
                this.perPage = w >= 1280 ? 5 : w >= 1024 ? 4 : w >= 768 ? 3 : w >= 640 ? 2 : 1;
                if (this.index > this.maxIndex) this.index = 0;
            },
            get maxIndex() {
                return Math.max(0, this.total - this.perPage);
            },
            next() {
                this.index = this.index >= this.maxIndex ? 0 : this.index + 1;
            },
            prev() {
                this.index = this.index <= 0 ? this.maxIndex : this.index - 1;
            },
            start() {
                this.stop();
                if (this.total <= this.perPage) return;
                this.timer = setInterval(() => this.next(), 3000);
            },
            stop() {
                if (this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            },
            slideStyle() {
                const pct = 100 / this.perPage;
                return `transform: translateX(-${this.index * pct}%);`;
            },
            itemStyle() {
                return `width: ${100 / this.perPage}%;`;
            }
        }"
        @mouseenter="stop()"
        @mouseleave="start()">

        <div class="relative">
            {{-- Glass frame around sharp banners --}}
            <div class="overflow-hidden rounded-[1.75rem] border border-white/40 bg-white/20 p-3 shadow-xl shadow-slate-900/10 backdrop-blur-2xl dark:border-white/10 dark:bg-slate-900/30">
                <div class="relative overflow-hidden rounded-[1.25rem]">
                    <div
                        class="flex transition-transform duration-700 ease-out"
                        :style="slideStyle()">
                        @foreach ($bannerEvents as $event)
                            <div class="shrink-0 px-1.5" :style="itemStyle()">
                                {{-- Blurred glass frame; image inside stays crisp --}}
                                <div class="aspect-[16/10] rounded-2xl border border-white/50 bg-white/30 p-2 shadow-lg shadow-slate-900/10 backdrop-blur-xl dark:border-white/15 dark:bg-slate-900/40">
                                    <div class="group relative h-full w-full overflow-hidden rounded-xl">
                                        @if ($event->cover)
                                            <img
                                                src="{{ asset('uploads/covers/events/' . $event->cover) }}"
                                                alt=""
                                                class="h-full w-full object-cover transition duration-700 group-hover:scale-105"
                                                loading="lazy">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-200 to-slate-300 dark:from-slate-700 dark:to-slate-800">
                                                <i class="bi bi-image text-2xl text-slate-400"></i>
                                            </div>
                                        @endif

                                        @auth
                                            <a href="{{ route('attendee.events.show', $event->id) }}"
                                                class="absolute inset-0 z-10"
                                                aria-label="{{ t(['en' => 'Open event', 'si' => 'ප්‍රසංග විවෘත කරන්න']) }}"></a>
                                        @else
                                            <button type="button"
                                                @click="$parent.promptLogin()"
                                                class="absolute inset-0 z-10"
                                                aria-label="{{ t(['en' => 'Open event', 'si' => 'ප්‍රසංග විවෘත කරන්න']) }}"></button>
                                        @endauth
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Controls (only when there are more banners than visible slots) --}}
            <template x-if="total > perPage">
                <div>
                    <button type="button"
                        @click="prev(); start()"
                        class="absolute left-0 top-1/2 z-20 flex h-10 w-10 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full
                            border border-white/50 bg-white/40 text-slate-800 shadow-lg backdrop-blur-xl
                            transition hover:bg-white/70 dark:border-white/15 dark:bg-slate-900/50 dark:text-white dark:hover:bg-slate-900/80"
                        aria-label="{{ t(['en' => 'Previous', 'si' => 'පෙර']) }}">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <button type="button"
                        @click="next(); start()"
                        class="absolute right-0 top-1/2 z-20 flex h-10 w-10 translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full
                            border border-white/50 bg-white/40 text-slate-800 shadow-lg backdrop-blur-xl
                            transition hover:bg-white/70 dark:border-white/15 dark:bg-slate-900/50 dark:text-white dark:hover:bg-slate-900/80"
                        aria-label="{{ t(['en' => 'Next', 'si' => 'ඊළඟ']) }}">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </template>
        </div>
    </section>
@endif
