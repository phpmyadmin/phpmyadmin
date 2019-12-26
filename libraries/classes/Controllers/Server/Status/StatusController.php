<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\Status\StatusController
 *
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Util;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Class StatusController
 * @package PhpMyAdmin\Controllers\Server\Status
 */
class StatusController extends AbstractController
{
    /**
     * @param ReplicationGui $replicationGui ReplicationGui instance
     *
     * @return string
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function index(ReplicationGui $replicationGui): string
    {
        global $replication_info;

        $traffic = [];
        $connections = [];
        $replication = '';
        if ($this->data->dataLoaded) {
            $networkTraffic = implode(
                ' ',
                Util::formatByteDown(
                    $this->data->status['Bytes_received'] + $this->data->status['Bytes_sent'],
                    3,
                    1
                )
            );
            $uptime = Util::timespanFormat($this->data->status['Uptime']);
            $startTime = Util::localisedDate($this->getStartTime());

            $traffic = $this->getTrafficInfo();

            $connections = $this->getConnectionsInfo();

            // display replication information
            if ($replication_info['master']['status']
                || $replication_info['slave']['status']
            ) {
                $replication = $this->getReplicationInfo($replicationGui);
            }
        }

        return $this->template->render('server/status/status/index', [
            'is_data_loaded' => $this->data->dataLoaded,
            'network_traffic' => $networkTraffic ?? null,
            'uptime' => $uptime ?? null,
            'start_time' => $startTime ?? null,
            'traffic' => $traffic,
            'connections' => $connections,
            'is_master' => $replication_info['master']['status'],
            'is_slave' => $replication_info['slave']['status'],
            'replication' => $replication,
        ]);
    }

    /**
     * @return int
     */
    private function getStartTime(): int
    {
        return (int) $this->dbi->fetchValue(
            'SELECT UNIX_TIMESTAMP() - ' . $this->data->status['Uptime']
        );
    }

    /**
     * @return array
     */
    private function getTrafficInfo(): array
    {
        $hourFactor = 3600 / $this->data->status['Uptime'];

        return [
            [
                'name' => __('Received'),
                'number' => implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_received'],
                        3,
                        1
                    )
                ),
                'per_hour' => implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_received'] * $hourFactor,
                        3,
                        1
                    )
                ),
            ],
            [
                'name' => __('Sent'),
                'number' => implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_sent'],
                        3,
                        1
                    )
                ),
                'per_hour' => implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_sent'] * $hourFactor,
                        3,
                        1
                    )
                ),
            ],
            [
                'name' => __('Total'),
                'number' => implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_received'] + $this->data->status['Bytes_sent'],
                        3,
                        1
                    )
                ),
                'per_hour' => implode(
                    ' ',
                    Util::formatByteDown(
                        ($this->data->status['Bytes_received'] + $this->data->status['Bytes_sent']) * $hourFactor,
                        3,
                        1
                    )
                ),
            ],
        ];
    }

    /**
     * @return array
     */
    private function getConnectionsInfo(): array
    {
        $hourFactor = 3600 / $this->data->status['Uptime'];

        $failedAttemptsPercentage = '---';
        $abortedPercentage = '---';
        if ($this->data->status['Connections'] > 0) {
            $failedAttemptsPercentage = Util::formatNumber(
                $this->data->status['Aborted_connects'] * 100 / $this->data->status['Connections'],
                0,
                2,
                true
            ) . '%';

            $abortedPercentage = Util::formatNumber(
                $this->data->status['Aborted_clients'] * 100 / $this->data->status['Connections'],
                0,
                2,
                true
            ) . '%';
        }

        return [
            [
                'name' => __('Max. concurrent connections'),
                'number' => Util::formatNumber(
                    $this->data->status['Max_used_connections'],
                    0
                ),
                'per_hour' => '---',
                'percentage' => '---',
            ],
            [
                'name' => __('Failed attempts'),
                'number' => Util::formatNumber(
                    $this->data->status['Aborted_connects'],
                    4,
                    1,
                    true
                ),
                'per_hour' => Util::formatNumber(
                    $this->data->status['Aborted_connects'] * $hourFactor,
                    4,
                    2,
                    true
                ),
                'percentage' => $failedAttemptsPercentage,
            ],
            [
                'name' => __('Aborted'),
                'number' => Util::formatNumber(
                    $this->data->status['Aborted_clients'],
                    4,
                    1,
                    true
                ),
                'per_hour' => Util::formatNumber(
                    $this->data->status['Aborted_clients'] * $hourFactor,
                    4,
                    2,
                    true
                ),
                'percentage' => $abortedPercentage,
            ],
            [
                'name' => __('Total'),
                'number' => Util::formatNumber(
                    $this->data->status['Connections'],
                    4,
                    0
                ),
                'per_hour' => Util::formatNumber(
                    $this->data->status['Connections'] * $hourFactor,
                    4,
                    2
                ),
                'percentage' => Util::formatNumber(100, 0, 2) . '%',
            ],
        ];
    }

    /**
     * @param ReplicationGui $replicationGui ReplicationGui instance
     *
     * @return string
     */
    private function getReplicationInfo(ReplicationGui $replicationGui): string
    {
        global $replication_info, $replication_types;

        $output = '';
        foreach ($replication_types as $type) {
            if (isset($replication_info[$type]['status'])
                && $replication_info[$type]['status']
            ) {
                $output .= $replicationGui->getHtmlForReplicationStatusTable($type);
            }
        }

        return $output;
    }
}
