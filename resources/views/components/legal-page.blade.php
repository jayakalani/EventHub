@props([
    'active' => 'terms',
    'title',
    'intro' => null,
    'icon' => 'bi-file-earmark-text',
    'toc' => [],
])

@php
    $updatedLabel = t(['en' => 'Last updated', 'si' => 'අවසන් යාවත්කාලීනය']);
    $updatedDate = date('F j, Y');
@endphp

<div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8 lg:py-14">
    {{-- Hero --}}
    <div class="mx-auto max-w-3xl text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary shadow-sm ring-1 ring-primary/15 dark:bg-primary/20 dark:text-primary-light dark:ring-primary/25">
            <i class="{{ $icon }} text-2xl" aria-hidden="true"></i>
        </div>

        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
            {{ $title }}
        </h1>

        @if ($intro)
            <p class="mx-auto mt-3 max-w-2xl text-sm leading-relaxed text-slate-600 sm:text-base dark:text-slate-300">
                {{ $intro }}
            </p>
        @endif

        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
            {{ $updatedLabel }} · {{ $updatedDate }}
        </p>

        {{-- Document tabs --}}
        <div class="mt-7 inline-flex rounded-full border border-slate-200/90 bg-white/80 p-1 shadow-sm backdrop-blur-sm dark:border-slate-700 dark:bg-slate-900/70">
            <a href="{{ route('terms') }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition sm:px-5 {{ $active === 'terms'
                    ? 'bg-primary text-white shadow-sm shadow-primary/25'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                {{ t(['en' => 'Terms & Conditions', 'si' => 'නියම සහ කොන්දේසි']) }}
            </a>
            <a href="{{ route('privacy') }}"
                class="rounded-full px-4 py-2 text-sm font-semibold transition sm:px-5 {{ $active === 'privacy'
                    ? 'bg-primary text-white shadow-sm shadow-primary/25'
                    : 'text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white' }}">
                {{ t(['en' => 'Privacy Policy', 'si' => 'රහස්‍යතා ප්‍රතිපත්තිය']) }}
            </a>
        </div>
    </div>

    {{-- Body: TOC + content --}}
    <div class="mt-10 grid gap-6 lg:mt-12 lg:grid-cols-[240px_minmax(0,1fr)] lg:gap-8 xl:grid-cols-[260px_minmax(0,1fr)]">
        {{-- Sticky TOC --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <nav class="rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-sm backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/80 sm:p-5"
                aria-label="{{ t(['en' => 'On this page', 'si' => 'මෙම පිටුවේ']) }}">
                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400 dark:text-slate-500">
                    {{ t(['en' => 'On this page', 'si' => 'මෙම පිටුවේ']) }}
                </p>
                <ul class="mt-3 space-y-1" id="legal-toc">
                    @foreach ($toc as $item)
                        <li>
                            <a href="#{{ $item['id'] }}"
                                data-toc-link="{{ $item['id'] }}"
                                class="legal-toc-link block rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </aside>

        {{-- Content card --}}
        <article class="rounded-2xl border border-slate-200/80 bg-white/90 p-6 shadow-sm backdrop-blur-sm sm:p-8 dark:border-slate-700/80 dark:bg-slate-900/80 lg:p-10">
            <div class="legal-content space-y-8 sm:space-y-10">
                {{ $slot }}
            </div>
        </article>
    </div>
</div>

<style>
    .legal-toc-link.is-active {
        background-color: rgb(37 99 235 / 0.1);
        color: #2563eb;
        font-weight: 600;
        box-shadow: inset 3px 0 0 #2563eb;
    }
    .dark .legal-toc-link.is-active {
        background-color: rgb(59 130 246 / 0.15);
        color: #93c5fd;
        box-shadow: inset 3px 0 0 #93c5fd;
    }
    .legal-content section {
        scroll-margin-top: 6.5rem;
    }
</style>

<script>
    (function () {
        const links = Array.from(document.querySelectorAll('[data-toc-link]'));
        if (!links.length) return;

        const sections = links
            .map((link) => document.getElementById(link.getAttribute('data-toc-link')))
            .filter(Boolean);

        const setActive = (id) => {
            links.forEach((link) => {
                link.classList.toggle('is-active', link.getAttribute('data-toc-link') === id);
            });
        };

        if (sections[0]) setActive(sections[0].id);

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((entry) => entry.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio);

                if (visible[0]) setActive(visible[0].target.id);
            },
            {
                rootMargin: '-20% 0px -55% 0px',
                threshold: [0.1, 0.35, 0.6],
            }
        );

        sections.forEach((section) => observer.observe(section));

        links.forEach((link) => {
            link.addEventListener('click', () => {
                setActive(link.getAttribute('data-toc-link'));
            });
        });
    })();
</script>
