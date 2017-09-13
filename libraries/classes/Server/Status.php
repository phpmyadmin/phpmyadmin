<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying server status
 *
 * @usedby  server_status.php
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Server;

use PhpMyAdmin\ReplicationGui;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Server\Status
 *
 * @package PhpMyAdmin
 */
class Status
{
    /**
     * Prints server status information: processes, connections and traffic
     *
     * @param Data $serverStatusData Server status data
     *
     * @return string
     */
    public static function getHtml(Data $serverStatusData)
    {
        //display the server state General Information
        $retval  = self::getHtmlForServerStateGeneralInfo($serverStatusData);

        //display the server state traffic information
        $retval .= self::getHtmlForServerStateTraffic($serverStatusData);

        //display the server state connection information
        $retval .= self::getHtmlForServerStateConnections($serverStatusData);

        // display replication information
        if ($GLOBALS['replication_info']['master']['status']
            || $GLOBALS['replication_info']['slave']['status']
        ) {
            $retval .= self::getHtmlForReplicationInfo();
        }

        return $retval;
    }

    /**
     * Prints server state General information
     *
     * @param Data $serverStatusData Server status data
     *
     * @return string
     */
    public static function getHtmlForServerStateGeneralInfo(Data $serverStatusData)
    {
        $start_time = $GLOBALS['dbi']->fetchValue(
            'SELECT UNIX_TIMESTAMP() - ' . $serverStatusData->status['Uptime']
        );

        $retval  = '<h3>';
        $bytes_received = $serverStatusData->status['Bytes_received'];
        $bytes_sent = $serverStatusData->status['Bytes_sent'];
        $retval .= sprintf(
            __('Network traffic since startup: %s'),
            implode(
                ' ',
                Util::formatByteDown(
                    $bytes_received + $bytes_sent,
                    3,
                    1
                )
            )
        );
        $retval .= '</h3>';
        $retval .= '<p>';
        $retval .= sprintf(
            __('This MySQL server has been running for %1$s. It started up on %2$s.'),
            Util::timespanFormat($serverStatusData->status['Uptime']),
            Util::localisedDate($start_time)
        ) . "\n";
        $retval .= '</p>';

        return $retval;
    }

    /**
     * Returns HTML to display replication information
     *
     * @return string HTML on replication
     */
    public static function getHtmlForReplicationInfo()
    {
        $retval = '<p class="notice clearfloat">';
        if ($GLOBALS['replication_info']['master']['status']
            && $GLOBALS['replication_info']['slave']['status']
        ) {
            $retval .= __(
                'This MySQL server works as <b>master</b> and '
                . '<b>slave</b> in <b>replication</b> process.'
            );
        } elseif ($GLOBALS['replication_info']['master']['status']) {
            $retval .= __(
                'This MySQL server works as <b>master</b> '
                . 'in <b>replication</b> process.'
            );
        } elseif ($GLOBALS['replication_info']['slave']['status']) {
            $retval .= __(
                'This MySQL server works as <b>slave</b> '
                . 'in <b>replication</b> process.'
            );
        }
        $retval .= '</p>';

        /*
         * if the server works as master or slave in replication process,
         * display useful information
         */
        $retval .= '<hr class="clearfloat" />';
        $retval .= '<h3><a name="replication">';
        $retval .= __('Replication status');
        $retval .= '</a></h3>';
        foreach ($GLOBALS['replication_types'] as $type) {
            if (isset($GLOBALS['replication_info'][$type]['status'])
                && $GLOBALS['replication_info'][$type]['status']
            ) {
                $retval .= ReplicationGui::getHtmlForReplicationStatusTable($type);
            }
        }

        return $retval;
    }

    /**
     * Prints server state traffic information
     *
     * @param Data $serverStatusData Server status data
     *
     * @return string
     */
    public static function getHtmlForServerStateTraffic(Data $serverStatusData)
    {
        $hour_factor    = 3600 / $serverStatusData->status['Uptime'];
        $retval  = '<table id="serverstatustraffic" class="width100 data noclick">';
        $retval .= '<thead>';
        $retval .= '<tr>';
        $retval .= '<th>';
        $retval .= __('Traffic') . '&nbsp;';
        $retval .=  Util::showHint(
            __(
                'On a busy server, the byte counters may overrun, so those statistics '
                . 'as reported by the MySQL server may be incorrect.'
            )
        );
        $retval .= '</th>';
        $retval .= '<th>#</th>';
        $retval .= '<th>&oslash; ' . __('per hour') . '</th>';
        $retval .= '</tr>';
        $retval .= '</thead>';
        $retval .= '<tbody>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Received') . '</th>';
        $retval .= '<td class="value">';
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                $serverStatusData->status['Bytes_received'], 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                $serverStatusData->status['Bytes_received'] * $hour_factor, 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Sent') . '</th>';
        $retval .= '<td class="value">';
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                $serverStatusData->status['Bytes_sent'], 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                $serverStatusData->status['Bytes_sent'] * $hour_factor, 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Total') . '</th>';
        $retval .= '<td class="value">';
        $bytes_received = $serverStatusData->status['Bytes_received'];
        $bytes_sent = $serverStatusData->status['Bytes_sent'];
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                $bytes_received + $bytes_sent, 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $bytes_received = $serverStatusData->status['Bytes_received'];
        $bytes_sent = $serverStatusData->status['Bytes_sent'];
        $retval .= implode(
            ' ',
            Util::formatByteDown(
                ($bytes_received + $bytes_sent) * $hour_factor, 3, 1
            )
        );
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '</tbody>';
        $retval .= '</table>';
        return $retval;
    }

    /**
     * Prints server state connections information
     *
     * @param Data $serverStatusData Server status data
     *
     * @return string
     */
    public static function getHtmlForServerStateConnections(Data $serverStatusData)
    {
        $hour_factor    = 3600 / $serverStatusData->status['Uptime'];
        $retval  = '<table id="serverstatusconnections" class="width100 data noclick">';
        $retval .= '<thead>';
        $retval .= '<tr>';
        $retval .= '<th>' . __('Connections') . '</th>';
        $retval .= '<th>#</th>';
        $retval .= '<th>&oslash; ' . __('per hour') . '</th>';
        $retval .= '<th>%</th>';
        $retval .= '</tr>';
        $retval .= '</thead>';
        $retval .= '<tbody>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Max. concurrent connections') . '</th>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Max_used_connections'], 0
        );
        $retval .= '</td>';
        $retval .= '<td class="value">--- </td>';
        $retval .= '<td class="value">--- </td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Failed attempts') . '</th>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Aborted_connects'], 4, 1, true
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Aborted_connects'] * $hour_factor, 4, 2, true
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        if ($serverStatusData->status['Connections'] > 0) {
            $abortNum = $serverStatusData->status['Aborted_connects'];
            $connectNum = $serverStatusData->status['Connections'];

            $retval .= Util::formatNumber(
                $abortNum * 100 / $connectNum,
                0, 2, true
            );
            $retval .= '%';
        } else {
            $retval .= '--- ';
        }
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Aborted') . '</th>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Aborted_clients'], 4, 1, true
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Aborted_clients'] * $hour_factor, 4, 2, true
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        if ($serverStatusData->status['Connections'] > 0) {
            $abortNum = $serverStatusData->status['Aborted_clients'];
            $connectNum = $serverStatusData->status['Connections'];

            $retval .= Util::formatNumber(
                $abortNum * 100 / $connectNum,
                0, 2, true
            );
            $retval .= '%';
        } else {
            $retval .= '--- ';
        }
        $retval .= '</td>';
        $retval .= '</tr>';
        $retval .= '<tr>';
        $retval .= '<th class="name">' . __('Total') . '</th>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Connections'], 4, 0
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(
            $serverStatusData->status['Connections'] * $hour_factor, 4, 2
        );
        $retval .= '</td>';
        $retval .= '<td class="value">';
        $retval .= Util::formatNumber(100, 0, 2);
        $retval .= '%</td>';
        $retval .= '</tr>';
        $retval .= '</tbody>';
        $retval .= '</table>';

        return $retval;
    }
}
