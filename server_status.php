<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require('./server_common.inc.php');


/**
 * Displays the links
 */
require('./server_links.inc.php');


/**
 * InnoDB status
 */
if (!empty($innodbstatus)) {
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_status.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
       . '    ' . $strInnodbStat . "\n"
       . '</h2>' . "\n";
    $res = PMA_DBI_query('SHOW INNODB STATUS;');
    $row = PMA_DBI_fetch_row($res);
    echo '<pre>' . "\n"
       . htmlspecialchars($row[0]) . "\n"
       . '</pre>' . "\n";
    PMA_DBI_free_result($res);
    require_once('./footer.inc.php');
}

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $GLOBALS['pmaThemeImage'] . 's_status.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
   . '    ' . $strServerStatus . "\n"
   . '</h2>' . "\n";


/**
 * Checks if the user is allowed to do what he tries to...
 */
if (!$is_superuser && !$cfg['ShowMysqlInfo']) {
    echo $strNoPrivileges;
    require_once('./footer.inc.php');
}


/**
 * Sends the query and buffers the result
 */
$res = PMA_DBI_query('SHOW STATUS;');
while ($row = PMA_DBI_fetch_row($res)) {
    $serverStatus[$row[0]] = $row[1];
}
PMA_DBI_free_result($res);
unset($res, $row);


/**
 * Displays the page
 */
//Uptime calculation
$res = PMA_DBI_query('SELECT UNIX_TIMESTAMP() - ' . $serverStatus['Uptime'] . ';');
$row = PMA_DBI_fetch_row($res);
echo sprintf($strServerStatusUptime, PMA_timespanFormat($serverStatus['Uptime']), PMA_localisedDate($row[0])) . "\n";
PMA_DBI_free_result($res);
unset($res, $row);
//Get query statistics
$queryStats = array();
$tmp_array = $serverStatus;
foreach ($tmp_array AS $name => $value) {
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
        <table border="0" cellpadding="5" cellspacing="0">
            <tr>
                <td valign="top">
                    <table border="0" cellpadding="2" cellspacing="1">
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
                    <table border="0" cellpadding="2" cellspacing="1">
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
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format(100, 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;%&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </li>
    <li>
        <!-- Queries -->
        <?php echo sprintf($strQueryStatistics, number_format($serverStatus['Questions'], 0, $number_decimal_separator, $number_thousands_separator)) . "\n"; ?>
        <table border="0" cellpadding="5" cellspacing="0">
            <tr>
                <td colspan="2">
                    <table border="0" cellpadding="2" cellspacing="1" width="100%">
                        <tr>
                            <th>&nbsp;<?php echo $strTotalUC; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerMinute; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerSecond; ?>&nbsp;</th>
                        </tr>
                        <tr>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format($serverStatus['Questions'], 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] * 60 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right">&nbsp;<?php echo number_format(($serverStatus['Questions'] / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <table border="0" cellpadding="2" cellspacing="1">
                        <tr>
                            <th colspan="2">&nbsp;<?php echo $strQueryType; ?>&nbsp;</th>
                            <th>&nbsp;&oslash;&nbsp;<?php echo $strPerHour; ?>&nbsp;</th>
                            <th>&nbsp;%&nbsp;</th>
                        </tr>
<?php

$useBgcolorOne = TRUE;
$countRows = 0;
foreach ($queryStats as $name => $value) {

// For the percentage column, use Questions - Connections, because
// the number of connections is not an item of the Query types
// but is included in Questions. Then the total of the percentages is 100.
?>
                        <tr>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($name); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format($value, 0, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($value * 3600 / $serverStatus['Uptime']), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo number_format(($value * 100 / ($serverStatus['Questions'] - $serverStatus['Connections'])), 2, $number_decimal_separator, $number_thousands_separator); ?>&nbsp;%&nbsp;</td>
                        </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
    if (++$countRows == ceil(count($queryStats) / 2)) {
        $useBgcolorOne = TRUE;
?>
                    </table>
                </td>
                <td valign="top">
                    <table border="0" cellpadding="2" cellspacing="1">
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
    <li>
        <!-- Other status variables -->
        <b><?php echo $strMoreStatusVars; ?></b><br />
        <table border="0" cellpadding="5" cellspacing="0">
            <tr>
                <td valign="top">
                    <table border="0" cellpadding="2" cellspacing="1">
                        <tr>
                            <th>&nbsp;<?php echo $strVar; ?>&nbsp;</th>
                            <th>&nbsp;<?php echo $strValue; ?>&nbsp;</th>
                        </tr>
<?php
    $useBgcolorOne = TRUE;
    $countRows = 0;
    foreach ($serverStatus AS $name => $value) {
?>
                        <tr>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars(str_replace('_', ' ', $name)); ?>&nbsp;</td>
                            <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo htmlspecialchars($value); ?>&nbsp;</td>
                        </tr>
<?php
        $useBgcolorOne = !$useBgcolorOne;
        if (++$countRows == ceil(count($serverStatus) / 3) || $countRows == ceil(count($serverStatus) * 2 / 3)) {
            $useBgcolorOne = TRUE;
?>
                    </table>
                </td>
                <td valign="top">
                    <table border="0" cellpadding="2" cellspacing="1">
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
$res = PMA_DBI_query('SHOW VARIABLES LIKE \'have_innodb\';');
if ($res) {
    $row = PMA_DBI_fetch_row($res);
    if (!empty($row[1]) && $row[1] == 'YES') {
?>
    <br />
    <li>
        <!-- InnoDB Status -->
        <a href="./server_status.php?<?php echo $url_query; ?>&amp;innodbstatus=1">
            <b><?php echo $strInnodbStat; ?></b>
        </a>
    </li>
<?php
    }
} else {
    unset($res);
}
?>
</ul>


<?php


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
