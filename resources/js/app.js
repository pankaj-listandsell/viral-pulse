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
initBreakingTicker();
initTableOfContents();
initFaqAccordion();
mountIslands();

