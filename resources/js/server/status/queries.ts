import { AJAX } from '../../modules/ajax.ts';

function buildQueryStatsChart (): void {
    const queryStatisticsChartCanvas = document.getElementById('query-statistics-chart') as HTMLCanvasElement;
    if (! queryStatisticsChartCanvas) {
        return;
    }

    const chartDataJson = queryStatisticsChartCanvas.getAttribute('data-chart-data');
    let chartData = null;
    try {
        chartData = JSON.parse(chartDataJson);
    } catch (e) {
        return;
    }

    if (! (chartData && 'labels' in chartData && 'data' in chartData)) {
        return;
    }

    new window.Chart(queryStatisticsChartCanvas, {
        type: 'pie',
        data: {
            labels: chartData.labels,
            datasets: [
                {
                    label: window.Messages.numberOfStatements,
                    data: chartData.data,
                }
            ],
        },
    });
}

AJAX.registerOnload('server/status/queries.js', function (): void {
    buildQueryStatsChart();
});
