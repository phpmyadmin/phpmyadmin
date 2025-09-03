import { CommonParams } from '../common.ts';

/**
 * Creates a Profiling Chart. Used in sql.ts
 * and in server/status/monitor.ts
 *
 * @param {string} target
 * @param {any[]} chartData
 * @param {string} legendPosition
 *
 * @return {object}
 */
export default function createProfilingChart (target, chartData, legendPosition) {
    const lang = CommonParams.get('lang');
    const numberFormat = new Intl.NumberFormat(lang.replace('_', '-'), {
        style: 'unit',
        unit: 'second',
        unitDisplay: 'long',
        notation: 'engineering',
    });

    return new window.Chart(target, {
        type: 'pie',
        data: { labels: chartData.labels, datasets: [{ data: chartData.data }] },
        options: {
            plugins: {
                legend: { position: legendPosition },
                tooltip: { callbacks: { label: context => context.parsed ? numberFormat.format(context.parsed) : '' } },
            },
        },
    });
}
