<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server\Status;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Response;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function implode;

/**
 * Object the server status page: processes, connections and traffic.
 */
class StatusController extends AbstractController
{
    /** @var ReplicationGui */
    private $replicationGui;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Response          $response
     * @param Data              $data
     * @param DatabaseInterface $dbi
     */
    public function __construct($response, Template $template, $data, ReplicationGui $replicationGui, $dbi)
    {
        parent::__construct($response, $template, $data);
        $this->replicationGui = $replicationGui;
        $this->dbi = $dbi;
    }

    public function index(): void
    {
        global $err_url;

        $err_url = Url::getFromRoute('/');

        if ($this->dbi->isSuperUser()) {
            $this->dbi->selectDb('mysql');
        }

        $replicationInfo = $this->data->getReplicationInfo();
        $primaryInfo = $replicationInfo->getPrimaryInfo();
        $replicaInfo = $replicationInfo->getReplicaInfo();

        $traffic = [];
        $connections = [];
        $replication = '';
        if ($this->data->dataLoaded) {
            // In some case the data was reported not to exist, check it for all keys
            if (isset($this->data->status['Bytes_received'], $this->data->status['Bytes_sent'])) {
                $networkTraffic = implode(
                    ' ',
                    Util::formatByteDown(
                        $this->data->status['Bytes_received'] + $this->data->status['Bytes_sent'],
                        3,
                        1
                    )
                );
            }
            if (isset($this->data->status['Uptime'])) {
                $uptime = Util::timespanFormat($this->data->status['Uptime']);
            }
            $startTime = Util::localisedDate($this->getStartTime());

            $traffic = $this->getTrafficInfo();

            $connections = $this->getConnectionsInfo();

            if ($primaryInfo['status']) {
                $replication .= $this->replicationGui->getHtmlForReplicationStatusTable('master');
            }
            if ($replicaInfo['status']) {
                $replication .= $this->replicationGui->getHtmlForReplicationStatusTable('slave');
            }
        }

        $this->render('server/status/status/index', [
            'is_data_loaded' => $this->data->dataLoaded,
            'network_traffic' => $networkTraffic ?? null,
            'uptime' => $uptime ?? null,
            'start_time' => $startTime ?? null,
            'traffic' => $traffic,
            'connections' => $connections,
            'is_master' => $primaryInfo['status'],
            'is_slave' => $replicaInfo['status'],
            'replication' => $replication,
        ]);
    }

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
}
