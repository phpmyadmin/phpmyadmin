/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Module import
 */
import { PMA_Messages as messages } from '../../variables/export_variables';

/**
 * @param {string} serverOs    Type of operating system
 *
 * @param {Object} presetCharts Charts already set
 */
export function getOsDetail (serverOs, presetCharts) {
    /* Add OS specific system info charts to the preset chart list */
    switch (serverOs) {
    case 'WINNT':
        $.extend(presetCharts, {
            'cpu': {
                title: messages.strSystemCPUUsage,
                series: [{
                    label: messages.strAverageLoad
                }],
                nodes: [{
                    dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                }],
                maxYLabel: 100
            },

            'memory': {
                title: messages.strSystemMemory,
                series: [{
                    label: messages.strTotalMemory,
                    fill: true
                }, {
                    dataType: 'memory',
                    label: messages.strUsedMemory,
                    fill: true
                }],
                nodes: [{ dataPoints: [{ type: 'memory', name: 'MemTotal' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },

            'swap': {
                title: messages.strSystemSwap,
                series: [{
                    label: messages.strTotalSwap,
                    fill: true
                }, {
                    label: messages.strUsedSwap,
                    fill: true
                }],
                nodes: [{ dataPoints: [{ type: 'memory', name: 'SwapTotal' }] },
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }] }
                ],
                maxYLabel: 0
            }
        });
        break;

    case 'Linux':
        $.extend(presetCharts, {
            'cpu': {
                title: messages.strSystemCPUUsage,
                series: [{
                    label: messages.strAverageLoad
                }],
                nodes: [{ dataPoints: [{ type: 'cpu', name: 'irrelevant' }], transformFn: 'cpu-linux' }],
                maxYLabel: 0
            },
            'memory': {
                title: messages.strSystemMemory,
                series: [
                    { label: messages.strBufferedMemory, fill: true },
                    { label: messages.strUsedMemory, fill: true },
                    { label: messages.strCachedMemory, fill: true },
                    { label: messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'Buffers' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'Cached' }],  valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: messages.strSystemSwap,
                series: [
                    { label: messages.strCachedSwap, fill: true },
                    { label: messages.strUsedSwap, fill: true },
                    { label: messages.strFreeSwap, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'SwapCached' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            }
        });
        break;

    case 'SunOS':
        $.extend(presetCharts, {
            'cpu': {
                title: messages.strSystemCPUUsage,
                series: [{
                    label: messages.strAverageLoad
                }],
                nodes: [{
                    dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                }],
                maxYLabel: 0
            },
            'memory': {
                title: messages.strSystemMemory,
                series: [
                    { label: messages.strUsedMemory, fill: true },
                    { label: messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: messages.strSystemSwap,
                series: [
                    { label: messages.strUsedSwap, fill: true },
                    { label: messages.strFreeSwap, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'SwapUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'SwapFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            }
        });
        break;
    }
}
