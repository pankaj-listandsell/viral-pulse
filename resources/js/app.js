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

initTheme();
initMobileNav();
initReadingProgress();
mountIslands();
