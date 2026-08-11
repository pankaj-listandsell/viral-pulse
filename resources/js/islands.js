/**
 * Vue islands.
 *
 * Pages are rendered as real HTML by Blade, so crawlers and social bots get
 * the full content without running any JavaScript. Vue is mounted only onto
 * the parts that genuinely need to be interactive, and the Vue runtime itself
 * is fetched only if the page actually contains an island - a plain article
 * page ships almost no JavaScript at all.
 *
 * Usage in Blade (build the array in @php first: Blade cannot parse a
 * multi-line array literal inside an HTML attribute):
 *   <div data-island="LikeButton" data-props="{{ json_encode($props) }}"></div>
 *   <div data-island="PostEditor" data-island-eager ...></div>
 */
const islands = import.meta.glob('./Islands/*.vue');

function readProps(el) {
    const raw = el.dataset.props;

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw);
    } catch {
        console.error('[island] data-props is not valid JSON', el);
        return {};
    }
}

async function mount(el) {
    const name = el.dataset.island;
    const loader = islands[`./Islands/${name}.vue`];

    if (!loader) {
        console.warn(`[island] no component named "${name}"`);
        return;
    }

    const [{ createApp }, module] = await Promise.all([import('vue'), loader()]);

    el.innerHTML = '';
    createApp(module.default, readProps(el)).mount(el);
    el.removeAttribute('data-island');
}

/**
 * Islands below the fold wait until they are near the viewport, so a long
 * article does not pay for its comment thread before the reader scrolls.
 * `data-island-eager` opts out, for anything above the fold or inside a form.
 */
function schedule(el) {
    if (el.dataset.islandEager !== undefined || !('IntersectionObserver' in window)) {
        mount(el);
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    observer.disconnect();
                    mount(entry.target);
                }
            });
        },
        { rootMargin: '200px' },
    );

    observer.observe(el);
}

export function mountIslands(root = document) {
    root.querySelectorAll('[data-island]').forEach(schedule);
}
