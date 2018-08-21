import { initProfilingTables, makeProfilingChart } from '../functions/Sql/SqlProfiling';

/**
 * @function makeProfilingChartInline Function for creating profiling chart
 * on clicking profiling checkbox
 *
 * @return void
 */
window.makeProfilingChartInline = function () {
    makeProfilingChart();
    initProfilingTables();
};
