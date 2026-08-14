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
 * Interactive, smooth hero slider with auto-play, touch swipe, and accessibility.
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

    let currentIndex = 0;
    let autoPlayTimer = null;
    const interval = 5000;

    function goToSlide(index) {
        if (index < 0) {
            index = slides.length - 1;
        } else if (index >= slides.length) {
            index = 0;
        }

        slides.forEach((slide, i) => {
            const links = slide.querySelectorAll('a, button');
            if (i === index) {
                slide.classList.remove('opacity-0', 'pointer-events-none', 'z-0');
                slide.classList.add('opacity-100', 'z-10', 'pointer-events-auto');
                slide.setAttribute('aria-hidden', 'false');
                links.forEach((l) => l.setAttribute('tabindex', '0'));
            } else {
                slide.classList.remove('opacity-100', 'z-10', 'pointer-events-auto');
                slide.classList.add('opacity-0', 'pointer-events-none', 'z-0');
                slide.setAttribute('aria-hidden', 'true');
                links.forEach((l) => l.setAttribute('tabindex', '-1'));
            }
        });

        dots.forEach((dot, i) => {
            if (i === index) {
                dot.className = 'h-2 rounded-full transition-all duration-300 w-8 bg-brand-600 dark:bg-brand-400';
            } else {
                dot.className = 'h-2 rounded-full transition-all duration-300 w-2 bg-gray-300 dark:bg-gray-700 hover:bg-gray-400';
            }
        });

        currentIndex = index;
    }

    function nextSlide() {
        goToSlide(currentIndex + 1);
    }

    function prevSlide() {
        goToSlide(currentIndex - 1);
    }

    function startAutoPlay() {
        stopAutoPlay();
        autoPlayTimer = setInterval(nextSlide, interval);
    }

    function stopAutoPlay() {
        if (autoPlayTimer) {
            clearInterval(autoPlayTimer);
            autoPlayTimer = null;
        }
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            nextSlide();
            startAutoPlay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            prevSlide();
            startAutoPlay();
        });
    }

    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            goToSlide(i);
            startAutoPlay();
        });
    });

    slider.addEventListener('mouseenter', stopAutoPlay);
    slider.addEventListener('mouseleave', startAutoPlay);

    // Touch swipe support for mobile
    let touchStartX = 0;

    slider.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoPlay();
    }, { passive: true });

    slider.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 40) {
            if (diff > 0) {
                nextSlide();
            } else {
                prevSlide();
            }
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

