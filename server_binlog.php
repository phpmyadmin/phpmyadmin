<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require_once('./server_common.inc.php');


/**
 * Displays the links
 */
require('./server_links.inc.php');


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
   . '    ' . $strBinaryLog . "\n"
   . '</h2>' . "\n";

if (!isset($log)) $log = '';

/**
 * Display log selector.
 */
if (count($binary_logs) > 1) {
    echo '<p><form action="server_binlog.php" method="get">';
    echo PMA_generate_common_hidden_inputs();
    echo $strSelectBinaryLog . ': ';
    echo '<select name="log">';
    foreach($binary_logs as $name) {
        echo '<option value="' . $name . '"' . ($name == $log ? ' selected="selected"' : '') . '>' . $name . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" value="' . $strGo . '" />';
    echo '</form><br /></p>';
}


$sql_query = 'SHOW BINLOG EVENTS';
if (!empty($log)) $sql_query .= ' IN \'' . $log . '\'';

/**
 * Sends the query and buffers the result
 */
$serverProcesses = array();
$res = PMA_DBI_query($sql_query);
while ($row = PMA_DBI_fetch_assoc($res)) {
    $serverProcesses[] = $row;
}
@PMA_DBI_free_result($res);
unset($res);
unset($row);

PMA_showMessage($GLOBALS['strSuccess']);


/**
 * Displays the page
 */
?>
<table border="0" cellpadding="2" cellspacing="1">
    <tr>
        <td colspan="6" align="center"><a href="./server_binlog.php?<?php echo $url_query . (!empty($log) ? '&amp;log=' . htmlspecialchars($log) : '' ) . (empty($full) ? '&amp;full=1' : ''); ?>" title="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>"><img src="<?php echo $pmaThemeImage . 's_' . (empty($full) ? 'full' : 'partial'); ?>text.png" width="50" height="20" border="0" alt="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>" /></a></td>
    </tr>
    <tr>
        <th>&nbsp;<?php echo $strBinLogName; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strBinLogPosition; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strBinLogEventType; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strBinLogServerId; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strBinLogOriginalPosition; ?>&nbsp;</th>
        <th>&nbsp;<?php echo $strBinLogInfo; ?>&nbsp;</th>
    </tr>
<?php
$useBgcolorOne = TRUE;
foreach ($serverProcesses as $value) {
    if (empty($full) && PMA_strlen($value['Info']) > $GLOBALS['cfg']['LimitChars']) {
        $value['Info'] = PMA_substr($value['Info'], 0, $GLOBALS['cfg']['LimitChars']) . '...';
    }
?>
    <tr>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $value['Log_name']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo $value['Pos']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo $value['Event_type']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo $value['Server_id']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>" align="right">&nbsp;<?php echo $value['Orig_log_pos']; ?>&nbsp;</td>
        <td bgcolor="<?php echo $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']; ?>">&nbsp;<?php echo htmlspecialchars($value['Info']); ?>&nbsp;</td>
    </tr>
<?php
    $useBgcolorOne = !$useBgcolorOne;
}
?>
<?php
?>
</table>
<?php


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
