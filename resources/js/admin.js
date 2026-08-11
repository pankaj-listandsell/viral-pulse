import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { Chart, registerables } from 'chart.js';
import { mountIslands } from './islands';

Chart.register(...registerables);
window.Chart = Chart;

Alpine.plugin(collapse);

/** Sidebar state. Collapsed width persists on desktop; mobile uses a drawer. */
Alpine.store('sidebar', {
    open: false,
    collapsed: localStorage.getItem('sidebar-collapsed') === 'true',

    toggle() {
        this.open = !this.open;
    },

    close() {
        this.open = false;
    },

    toggleCollapsed() {
        this.collapsed = !this.collapsed;
        localStorage.setItem('sidebar-collapsed', String(this.collapsed));
    },
});

Alpine.store('theme', {
    dark: document.documentElement.classList.contains('dark'),

    toggle() {
        this.dark = !this.dark;
        document.documentElement.classList.toggle('dark', this.dark);
        localStorage.setItem('theme', this.dark ? 'dark' : 'light');
    },
});

Alpine.store('toasts', {
    items: [],
    nextId: 1,

    push(type, message) {
        if (!message) {
            return;
        }

        const id = this.nextId++;
        this.items.push({ id, type, message });

        setTimeout(() => this.dismiss(id), 5000);
    },

    dismiss(id) {
        this.items = this.items.filter((toast) => toast.id !== id);
    },
});

/**
 * Confirmation dialog for destructive actions. The form is only submitted
 * after the user confirms, so no delete can happen on a single stray click.
 */
Alpine.data('confirmable', (message = 'This cannot be undone.') => ({
    open: false,
    message,

    confirm() {
        this.open = false;
        this.$refs.form.submit();
    },
}));

window.Alpine = Alpine;
Alpine.start();

/**
 * Charts are declared in Blade as <canvas data-chart='{...}'> so the view stays
 * declarative and no chart config is duplicated in a script tag per page.
 */
function initCharts() {
    const dark = document.documentElement.classList.contains('dark');
    const grid = dark ? 'rgba(255,255,255,0.07)' : 'rgba(0,0,0,0.06)';
    const tick = dark ? '#9ca3af' : '#6b7280';

    document.querySelectorAll('[data-chart]').forEach((canvas) => {
        let config;

        try {
            config = JSON.parse(canvas.dataset.chart);
        } catch {
            console.error('[chart] invalid data-chart JSON', canvas);
            return;
        }

        const isBar = config.type === 'bar';

        new Chart(canvas, {
            type: config.type ?? 'line',
            data: {
                labels: config.labels,
                datasets: [
                    {
                        label: config.label ?? '',
                        data: config.data,
                        borderColor: config.color ?? '#e7000b',
                        backgroundColor: isBar
                            ? config.color ?? '#e7000b'
                            : 'rgba(231, 0, 11, 0.12)',
                        borderWidth: 2,
                        fill: !isBar,
                        tension: 0.35,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        borderRadius: isBar ? 6 : 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { intersect: false, mode: 'index' },
                plugins: { legend: { display: false } },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: tick, maxRotation: 0, autoSkipPadding: 16 },
                        border: { color: grid },
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: grid },
                        ticks: { color: tick, precision: 0, maxTicksLimit: 5 },
                        border: { display: false },
                    },
                },
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', initCharts);

// The post editor, tag input and image picker are Vue islands inside otherwise
// plain Blade forms.
mountIslands();
