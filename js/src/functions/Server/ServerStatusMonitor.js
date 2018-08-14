import { PMA_Messages as PMA_messages } from '../../variables/export_variables';
export function getOsDetail (server_os, presetCharts) {
    /* Add OS specific system info charts to the preset chart list */
    switch (server_os) {
    case 'WINNT':
        $.extend(presetCharts, {
            'cpu': {
                title: PMA_messages.strSystemCPUUsage,
                series: [{
                    label: PMA_messages.strAverageLoad
                }],
                nodes: [{
                    dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                }],
                maxYLabel: 100
            },

            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [{
                    label: PMA_messages.strTotalMemory,
                    fill: true
                }, {
                    dataType: 'memory',
                    label: PMA_messages.strUsedMemory,
                    fill: true
                }],
                nodes: [{ dataPoints: [{ type: 'memory', name: 'MemTotal' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },

            'swap': {
                title: PMA_messages.strSystemSwap,
                series: [{
                    label: PMA_messages.strTotalSwap,
                    fill: true
                }, {
                    label: PMA_messages.strUsedSwap,
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
                title: PMA_messages.strSystemCPUUsage,
                series: [{
                    label: PMA_messages.strAverageLoad
                }],
                nodes: [{ dataPoints: [{ type: 'cpu', name: 'irrelevant' }], transformFn: 'cpu-linux' }],
                maxYLabel: 0
            },
            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [
                    { label: PMA_messages.strBufferedMemory, fill: true },
                    { label: PMA_messages.strUsedMemory, fill: true },
                    { label: PMA_messages.strCachedMemory, fill: true },
                    { label: PMA_messages.strFreeMemory, fill: true }
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
                title: PMA_messages.strSystemSwap,
                series: [
                    { label: PMA_messages.strCachedSwap, fill: true },
                    { label: PMA_messages.strUsedSwap, fill: true },
                    { label: PMA_messages.strFreeSwap, fill: true }
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
                title: PMA_messages.strSystemCPUUsage,
                series: [{
                    label: PMA_messages.strAverageLoad
                }],
                nodes: [{
                    dataPoints: [{ type: 'cpu', name: 'loadavg' }]
                }],
                maxYLabel: 0
            },
            'memory': {
                title: PMA_messages.strSystemMemory,
                series: [
                    { label: PMA_messages.strUsedMemory, fill: true },
                    { label: PMA_messages.strFreeMemory, fill: true }
                ],
                nodes: [
                    { dataPoints: [{ type: 'memory', name: 'MemUsed' }], valueDivisor: 1024 },
                    { dataPoints: [{ type: 'memory', name: 'MemFree' }], valueDivisor: 1024 }
                ],
                maxYLabel: 0
            },
            'swap': {
                title: PMA_messages.strSystemSwap,
                series: [
                    { label: PMA_messages.strUsedSwap, fill: true },
                    { label: PMA_messages.strFreeSwap, fill: true }
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
