<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require('./server_common.inc.php3');

/**
 * Handles some variables that may have been sent by the calling script
 */
if (isset($db)) {
    unset($db);
}
if (isset($table)) {
    unset($table);
}

/**
 * Displays the links
 */
require('./server_links.inc.php3');

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . '    ' . $strServerStatus . "\n"
   . '</h2>' . "\n";

/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser && !$cfg['ShowMysqlInfo']) {
    echo $strNoPrivileges;
    include('./footer.inc.php3');
    exit;
}

/**
 * Sends the query and buffers the result
 */
$res = @PMA_mysql_query('SHOW STATUS;', $userlink) or PMA_mysqlDie(PMA_mysql_error($userlink), 'SHOW STATUS;');
while ($row = PMA_mysql_fetch_row($res)) {
    $serverStatus[$row[0]] = $row[1];
}
@mysql_free_result($res);
unset($res);
unset($row);

/**
 * Displays the page
 */
//Uptime calculation
$res = @PMA_mysql_query('SELECT UNIX_TIMESTAMP() - ' . $serverStatus['Uptime'] . ';');
$row = PMA_mysql_fetch_row($res);
echo sprintf($strServerStatusUptime, PMA_timespanFormat($serverStatus['Uptime']), PMA_localisedDate($row[0])) . "\n";
mysql_free_result($res);
unset($res);
unset($row);
//Get query statistics
$queryStats = array();
$tmp_array = $serverStatus;
while (list($name, $value) = each($tmp_array)) {
    if (substr($name, 0, 4) == 'Com_') {
        $queryStats[str_replace('_', ' ', substr($name, 4))] = $value;
        unset($serverStatus[$name]);
    }
}
unset($tmp_array);
?>
<ul>
    <li>
        <!-- Server Traffic -->
        <?php echo $strServerTrafficNotes; ?><br />
        <table border="0">
            <tr>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th colspan="2">&nbsp;<?php echo $strTraffic; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $strReceived; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'])); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $strSent; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_sent'])); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_sent'] * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">&nbsp;<?php echo $strTotalUC; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent'])); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo join(' ', PMA_formatByteDown(($serverStatus['Bytes_received'] + $serverStatus['Bytes_sent']) * 3600 / $serverStatus['Uptime'])); ?>&nbsp;</td>
                        </tr>
                    </table>
                </td>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th colspan="2">&nbsp;<?php echo $strConnections; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                            <th>&nbsp;%&nbsp;</th>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $strFailedAttempts; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format($serverStatus['Aborted_connects'], 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Aborted_connects'] * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_connects'] * 100 / $serverStatus['Connections']), 2, $number_decimal_separator, $number_thousands_separator) . '&nbsp;%' : '---'; ?>&nbsp;</td>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $strAbortedClients; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format($serverStatus['Aborted_clients'], 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Aborted_clients'] * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo ($serverStatus['Connections'] > 0 ) ? number_format(($serverStatus['Aborted_clients'] * 100 / $serverStatus['Connections']), 2 , $number_decimal_separator, $number_thousands_separator) . '&nbsp;%' : '---'; ?>&nbsp;</td>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>">&nbsp;<?php echo $strTotalUC; ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format($serverStatus['Connections'], 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Connections'] * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;100,00&nbsp;%&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </li>
    <br />
    <li>
        <!-- Queries -->
        <?php echo sprintf($strQueryStatistics, number_format($serverStatus['Questions'], 0, $number_decimal_separator, $number_thousands_separator)); ?><br />
        <table border="0">
            <tr>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th colspan="2">&nbsp;<?php echo $strQueryType; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                            <th>&nbsp;%&nbsp;</th>
                        </tr>
<?php

$useBgcolorOne = TRUE;
$countRows = 0;
while (list($name, $value) = each($queryStats)) {
?>
                        <tr>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($name); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format($value, 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($value * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($value * 100 / $serverStatus['Questions']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;%&nbsp;</td>
                        </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
    if (++$countRows == ceil(count($queryStats) / 2)) {
        $useBgcolorOne = TRUE;
?>
                    </table>
                </td>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th colspan="2">&nbsp;<?php echo $strQueryType; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                            <th>&nbsp;%&nbsp;</th>
                        </tr>
<?php
    }
}
unset($countRows);
unset($useBgcolorOne);

?>
                    </table>
                </td>
            </tr>
        </table>
    </li>
<?php
//Unset used variables
unset($serverStatus['Aborted_clients']);
unset($serverStatus['Aborted_connects']);
unset($serverStatus['Bytes_received']);
unset($serverStatus['Bytes_sent']);
unset($serverStatus['Connections']);
unset($serverStatus['Questions']);
unset($serverStatus['Uptime']);

if (!empty($serverStatus)) {
?>
    <br />
    <li>
        <!-- Other status variables -->
        <b><?php echo $strMoreStatusVars; ?></b><br />
        <table border="0">
            <tr>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th>&nbsp;<?php echo $strVar; ?>&nbsp;</th>
                            <th>&nbsp;<?php echo $strValue; ?>&nbsp;</th>
                        </tr>
<?php
$useBgcolorOne = TRUE;
$countRows = 0;
while (list($name, $value) = each($serverStatus)) {
?>
                        <tr>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($value); ?>&nbsp;</td>
                        </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
    if (++$countRows == ceil(count($serverStatus) / 3) || $countRows == ceil(count($serverStatus) * 2 / 3)) {
        $useBgcolorOne = TRUE;
?>
                    </table>
                </td>
                <td valign="top">
                    <table border="0">
                        <tr>
                            <th>&nbsp;<?php echo $strVar; ?>&nbsp;</th>
                            <th>&nbsp;<?php echo $strValue; ?>&nbsp;</th>
                        </tr>
<?php
    }
}
unset($useBgcolorOne);
?>
                    </table>
                </td>
            </tr>
        </table>
    </li>
<?php
}
?>
</ul>


<?php

/**
 * Sends the footer
 */
require('./footer.inc.php3');

?>