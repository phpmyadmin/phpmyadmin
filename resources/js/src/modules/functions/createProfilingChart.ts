import { CommonParams } from '../common.ts';

/**
 * Creates a Profiling Chart. Used in sql.js
 * and in server/status/monitor.js
 *
 * @param {string} target
 * @param {any[]} data
 *
 * @return {object}
 */
export default function createProfilingChart (target, data) {
    const lang = CommonParams.get('lang');
    const numberFormat = new Intl.NumberFormat(lang.replace('_', '-'), {
        style: 'unit',
        unit: 'second',
        unitDisplay: 'long',
        notation: 'engineering',
    });

    return new window.Chart(target, {
        type: 'pie',
        data: { labels: data.map(row => row[0]), datasets: [{ data: data.map(row => row[1]) }] },
        options: {
            plugins: {
                legend: { position: 'right' },
                tooltip: { callbacks: { label: context => context.parsed ? numberFormat.format(context.parsed) : '' } },
            },
        },
    });
}
