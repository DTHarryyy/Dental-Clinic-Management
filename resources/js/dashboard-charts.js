import {
    Chart,
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    PointElement,
    BarElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend,
} from 'chart.js';

// Only the line/bar/doughnut charts DashboardAnalytics and ClinicReportService actually
// emit (see their `chart()`/`distributionChart()`/`groupedChart()` builders) are
// registered here — `chart.js/auto` would pull in every controller/scale Chart.js ships,
// which is most of this module's weight.
Chart.register(
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    PointElement,
    BarElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler,
    Tooltip,
    Legend
);

const palette = ['#10b981', '#3b82f6', '#8b5cf6', '#ef4444', '#f59e0b', '#14b8a6'];
const instances = new Set();

function money(value) {
    return `₱${Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function tooltipLabel(context, format) {
    const horizontal = context.chart.options.indexAxis === 'y';
    const value = horizontal ? context.parsed.x : (context.parsed.y ?? context.parsed);
    return `${context.dataset.label}: ${format === 'currency' ? money(value) : Number(value).toLocaleString('en-PH')}`;
}

function compactNumber(value, format) {
    return format === 'currency'
        ? `₱${Intl.NumberFormat('en-PH', { notation: 'compact', maximumFractionDigits: 1 }).format(value)}`
        : Number(value).toLocaleString('en-PH');
}

function shortLabel(value) {
    const label = String(value);
    return label.length > 24 ? `${label.slice(0, 22)}…` : label;
}

function baseOptions(format) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    return {
        responsive: true,
        maintainAspectRatio: false,
        animation: reducedMotion ? false : { duration: 350 },
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: { callbacks: { label: (context) => tooltipLabel(context, context.dataset.analyticsFormat || format) } },
        },
    };
}

function configFor(chart) {
    const format = chart.datasets[0]?.format || 'number';
    const datasets = chart.datasets.map((dataset) => ({ ...dataset, analyticsFormat: dataset.format, format: undefined }));
    const options = baseOptions(format);

    if (chart.type === 'doughnut') {
        datasets[0] = {
            ...datasets[0],
            backgroundColor: palette.slice(0, chart.labels.length),
            borderColor: '#ffffff',
            borderWidth: 3,
            hoverOffset: 4,
        };
        options.cutout = chart.compact ? '72%' : '66%';
        options.interaction = { intersect: true, mode: 'nearest' };
    } else if (chart.type === 'bar') {
        datasets.forEach((dataset, index) => Object.assign(dataset, {
            backgroundColor: `${palette[index % palette.length]}c7`,
            borderColor: palette[index % palette.length],
            borderWidth: 1,
            borderRadius: chart.variant === 'comparison' ? 5 : 7,
            borderSkipped: false,
            maxBarThickness: chart.variant === 'comparison' ? 24 : 30,
            categoryPercentage: chart.variant === 'comparison' ? 0.72 : 0.64,
            barPercentage: chart.variant === 'comparison' ? 0.86 : 0.78,
        }));
        const horizontal = chart.orientation !== 'vertical';
        options.indexAxis = horizontal ? 'y' : 'x';
        options.scales = horizontal ? {
            x: {
                beginAtZero: true,
                ticks: { precision: format === 'number' ? 0 : undefined, color: '#64748b', callback: (value) => compactNumber(value, format) },
                grid: { color: 'rgba(148, 163, 184, 0.14)' },
            },
            y: { ticks: { color: '#475569', callback(value) { return shortLabel(this.getLabelForValue(value)); } }, grid: { display: false } },
        } : {
            y: {
                beginAtZero: true,
                ticks: { precision: format === 'number' ? 0 : undefined, color: '#64748b', callback: (value) => compactNumber(value, format) },
                grid: { color: 'rgba(148, 163, 184, 0.14)' },
            },
            x: { ticks: { color: '#475569', callback(value) { return shortLabel(this.getLabelForValue(value)); } }, grid: { display: false } },
        };
    } else {
        datasets.forEach((dataset, index) => Object.assign(dataset, {
            tension: 0.35,
            fill: index === 0,
            borderColor: palette[index % palette.length],
            backgroundColor: `${palette[index % palette.length]}17`,
            pointBackgroundColor: palette[index % palette.length],
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: chart.labels.length > 20 ? 2 : 4,
            pointHoverRadius: 5,
        }));
        options.scales = {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: format === 'number' ? 0 : undefined,
                    color: '#64748b',
                    callback: (value) => compactNumber(value, format),
                },
                grid: { color: 'rgba(148, 163, 184, 0.14)' },
            },
            x: {
                ticks: { color: '#64748b', maxRotation: 0, autoSkip: true, maxTicksLimit: 8 },
                grid: { display: false },
            },
        };
    }

    return { type: chart.type, data: { labels: chart.labels, datasets }, options };
}

export function destroyDashboardCharts() {
    instances.forEach((chart) => chart.destroy());
    instances.clear();
    window.__analyticsChartCount = 0;
}

export function initDashboardCharts() {
    const source = document.querySelector('[data-analytics-charts], [data-dashboard-charts]');
    if (!source) return;

    destroyDashboardCharts();

    let charts;
    try {
        charts = JSON.parse(source.textContent);
    } catch {
        return;
    }

    document.querySelectorAll('canvas[data-analytics-chart], canvas[data-dashboard-chart]').forEach((canvas) => {
        const chart = charts[canvas.dataset.analyticsChart || canvas.dataset.dashboardChart];
        if (!chart) return;
        Chart.getChart(canvas)?.destroy();
        instances.add(new Chart(canvas, configFor(chart)));
    });
    window.__analyticsChartCount = instances.size;
}
