{{-- Global scroll-to-top control --}}
<button
    type="button"
    id="scroll-to-top"
    class="scroll-to-top fixed bottom-6 right-5 z-[90] flex h-11 w-11 items-center justify-center rounded-full
        bg-primary text-white shadow-lg shadow-primary/30 ring-1 ring-white/30
        transition-all duration-300 ease-out
        hover:bg-primary-dark hover:shadow-xl hover:shadow-primary/40 hover:-translate-y-0.5
        focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2
        dark:ring-white/10 sm:bottom-8 sm:right-8 sm:h-12 sm:w-12"
    aria-label="{{ t(['en' => 'Scroll to top', 'si' => 'ඉහළට යන්න']) }}"
    title="{{ t(['en' => 'Scroll to top', 'si' => 'ඉහළට යන්න']) }}"
>
    <i class="bi bi-arrow-up text-lg sm:text-xl scroll-to-top__icon" aria-hidden="true"></i>
</button>

<style>
    #scroll-to-top {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transform: translateY(12px) scale(0.92);
    }

    #scroll-to-top.is-visible {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    #scroll-to-top .scroll-to-top__icon {
        display: inline-block;
        animation: scrollToTopBounce 1.6s ease-in-out infinite;
    }

    #scroll-to-top:hover .scroll-to-top__icon,
    #scroll-to-top:focus-visible .scroll-to-top__icon {
        animation: none;
    }

    @keyframes scrollToTopBounce {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
    }

    @media (prefers-reduced-motion: reduce) {
        #scroll-to-top,
        #scroll-to-top .scroll-to-top__icon {
            animation: none !important;
            transition: opacity 0.2s ease, visibility 0.2s ease !important;
        }
    }
</style>

<script>
    (function () {
        const button = document.getElementById('scroll-to-top');
        if (!button) return;

        const threshold = 280;
        let ticking = false;

        const update = () => {
            const scrolled = window.scrollY || document.documentElement.scrollTop;
            button.classList.toggle('is-visible', scrolled > threshold);
            ticking = false;
        };

        const onScroll = () => {
            if (!ticking) {
                window.requestAnimationFrame(update);
                ticking = true;
            }
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        update();

        button.addEventListener('click', () => {
            const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            window.scrollTo({
                top: 0,
                behavior: prefersReduced ? 'auto' : 'smooth',
            });
        });
    })();
</script>
