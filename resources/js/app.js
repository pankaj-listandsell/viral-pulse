import { mountIslands } from './islands';

/*
 * The public bundle deliberately stays tiny. Alpine and Chart.js belong to the
 * admin panel; a reader only needs a theme toggle, a mobile menu, and whatever
 * islands the page actually declares.
 */

function initTheme() {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const dark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', dark ? 'dark' : 'light');
            document
                .querySelectorAll('[data-theme-icon]')
                .forEach((icon) => icon.hidden = icon.dataset.themeIcon !== (dark ? 'dark' : 'light'));
        });
    });

    const dark = document.documentElement.classList.contains('dark');
    document
        .querySelectorAll('[data-theme-icon]')
        .forEach((icon) => icon.hidden = icon.dataset.themeIcon !== (dark ? 'dark' : 'light'));
}

function initMobileNav() {
    const panel = document.querySelector('[data-nav-panel]');
    const toggle = document.querySelector('[data-nav-toggle]');

    if (!panel || !toggle) {
        return;
    }

    toggle.addEventListener('click', () => {
        const open = panel.hasAttribute('hidden');
        panel.toggleAttribute('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
    });
}

/** Progress bar and back-to-top, both cheap and both only on article pages. */
function initReadingProgress() {
    const bar = document.querySelector('[data-reading-progress]');

    if (!bar) {
        return;
    }

    const update = () => {
        const scrollable = document.documentElement.scrollHeight - window.innerHeight;
        const progress = scrollable > 0 ? (window.scrollY / scrollable) * 100 : 0;
        bar.style.width = `${Math.min(100, progress)}%`;
    };

    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update, { passive: true });
}

/**
 * Hero slider: auto-play, dots, arrows, touch swipe.
 *
 * Inactive slides are display:none rather than opacity-0. An element that is
 * merely transparent is still in the viewport, so `loading="lazy"` does not
 * defer it and every slide downloaded on first paint. Hiding them outright
 * defers the work; the fade is kept by unhiding a frame before the opacity
 * flips.
 */
function initHeroSlider() {
    const slider = document.querySelector('[data-hero-slider]');

    if (!slider) {
        return;
    }

    const slides = slider.querySelectorAll('[data-slide]');

    if (slides.length <= 1) {
        return;
    }

    const prevBtn = slider.querySelector('[data-slider-prev]');
    const nextBtn = slider.querySelector('[data-slider-next]');
    const dots = slider.querySelectorAll('[data-slider-dot]');
    const calm = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let currentIndex = 0;
    let autoPlayTimer = null;

    function goToSlide(index) {
        const target = (index + slides.length) % slides.length;

        slides.forEach((slide, i) => {
            const links = slide.querySelectorAll('a, button');
            const active = i === target;

            if (active) {
                slide.hidden = false;
                // Read a layout property so the browser paints the unhidden
                // slide before the opacity transition starts; without it the
                // change is batched and the fade never runs.
                void slide.offsetWidth;
            }

            slide.classList.toggle('opacity-100', active);
            slide.classList.toggle('opacity-0', ! active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
            links.forEach((link) => link.setAttribute('tabindex', active ? '0' : '-1'));

            if (! active) {
                // Wait out the fade before removing it from the layout.
                setTimeout(() => {
                    if (slide.classList.contains('opacity-0')) {
                        slide.hidden = true;
                    }
                }, 500);
            }
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle('w-8', i === target);
            dot.classList.toggle('bg-brand-600', i === target);
            dot.classList.toggle('dark:bg-brand-400', i === target);
            dot.classList.toggle('w-2', i !== target);
            dot.classList.toggle('bg-gray-300', i !== target);
            dot.classList.toggle('dark:bg-gray-700', i !== target);
            dot.setAttribute('aria-current', i === target ? 'true' : 'false');
        });

        currentIndex = target;
    }

    function startAutoPlay() {
        stopAutoPlay();

        if (! calm) {
            autoPlayTimer = setInterval(() => goToSlide(currentIndex + 1), 6000);
        }
    }

    function stopAutoPlay() {
        clearInterval(autoPlayTimer);
        autoPlayTimer = null;
    }

    nextBtn?.addEventListener('click', () => {
        goToSlide(currentIndex + 1);
        startAutoPlay();
    });

    prevBtn?.addEventListener('click', () => {
        goToSlide(currentIndex - 1);
        startAutoPlay();
    });

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            goToSlide(i);
            startAutoPlay();
        });
    });

    slider.addEventListener('mouseenter', stopAutoPlay);
    slider.addEventListener('mouseleave', startAutoPlay);

    // Nothing is moving while the tab is in the background; a timer that keeps
    // firing there only costs battery.
    document.addEventListener('visibilitychange', () => {
        document.hidden ? stopAutoPlay() : startAutoPlay();
    });

    let touchStartX = 0;

    slider.addEventListener('touchstart', (event) => {
        touchStartX = event.changedTouches[0].screenX;
        stopAutoPlay();
    }, { passive: true });

    slider.addEventListener('touchend', (event) => {
        const distance = touchStartX - event.changedTouches[0].screenX;

        if (Math.abs(distance) > 40) {
            goToSlide(currentIndex + (distance > 0 ? 1 : -1));
        }

        startAutoPlay();
    }, { passive: true });

    startAutoPlay();
}

/**
 * Auto-scrolling Breaking News ticker in the header bar.
 */
function initBreakingTicker() {
    const wrapper = document.querySelector('[data-ticker-wrapper]');
    if (!wrapper) return;

    const track = wrapper.querySelector('[data-ticker-track]');
    const items = track ? track.querySelectorAll('[data-ticker-item]') : [];
    if (!track || items.length <= 1) return;

    const prevBtn = document.querySelector('[data-ticker-prev]');
    const nextBtn = document.querySelector('[data-ticker-next]');

    let currentIndex = 0;
    let timer = null;
    const itemHeight = 20;

    function update() {
        track.style.transform = `translateY(-${currentIndex * itemHeight}px)`;
    }

    function next() {
        currentIndex = (currentIndex + 1) % items.length;
        update();
    }

    function prev() {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        update();
    }

    function start() {
        stop();
        timer = setInterval(next, 4000);
    }

    function stop() {
        if (timer) clearInterval(timer);
    }

    if (nextBtn) nextBtn.addEventListener('click', () => { next(); start(); });
    if (prevBtn) prevBtn.addEventListener('click', () => { prev(); start(); });

    wrapper.addEventListener('mouseenter', stop);
    wrapper.addEventListener('mouseleave', start);

    start();
}

/**
 * Auto Table of Contents generator for article pages.
 */
function initTableOfContents() {
    const tocBox = document.getElementById('article-toc');
    const tocList = document.getElementById('toc-list');
    const tocToggle = document.getElementById('toc-toggle');
    const tocChevron = document.getElementById('toc-chevron');
    const prose = document.querySelector('.prose');

    if (!tocBox || !tocList || !prose) return;

    const headings = prose.querySelectorAll('h2, h3');
    if (headings.length < 2) return;

    tocList.innerHTML = '';

    headings.forEach((heading, idx) => {
        const text = heading.textContent.trim();
        if (!text) return;

        let id = heading.id;
        if (!id) {
            id = 'section-' + (idx + 1);
            heading.id = id;
        }

        const isH3 = heading.tagName.toLowerCase() === 'h3';
        const a = document.createElement('a');
        a.href = '#' + id;
        a.textContent = text;
        a.className = isH3
            ? 'block pl-4 text-xs text-gray-500 hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-400 transition'
            : 'block font-medium text-gray-700 hover:text-brand-600 dark:text-gray-200 dark:hover:text-brand-400 transition';

        tocList.appendChild(a);
    });

    tocBox.classList.remove('hidden');

    if (tocToggle) {
        let isCollapsed = false;
        tocToggle.addEventListener('click', () => {
            isCollapsed = !isCollapsed;
            tocList.classList.toggle('hidden', isCollapsed);
            if (tocChevron) {
                tocChevron.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
            }
        });
    }
}

/**
 * Enhances FAQ and details/summary elements inside article content.
 */
function initFaqAccordion() {
    const details = document.querySelectorAll('.prose details');
    details.forEach((el) => {
        el.classList.add('rounded-xl', 'border', 'border-gray-200/80', 'dark:border-gray-800', 'p-4', 'my-3', 'bg-gray-50/50', 'dark:bg-gray-900/30', 'transition-all');
        const summary = el.querySelector('summary');
        if (summary) {
            summary.classList.add('cursor-pointer', 'font-semibold', 'text-gray-900', 'dark:text-white', 'select-none');
        }
    });
}

initTheme();
initMobileNav();
initReadingProgress();
initHeroSlider();
initBreakingTicker();
initTableOfContents();
initFaqAccordion();
mountIslands();

