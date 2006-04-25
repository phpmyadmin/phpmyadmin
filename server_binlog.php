<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once './libraries/common.lib.php';

/**
 * Does the common work
 */
require_once './libraries/server_common.inc.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';


/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" alt="" />' : '' )
   . '    ' . $strBinaryLog . "\n"
   . '</h2>' . "\n";

if (!isset($log)) {
    $log = '';
}

/**
 * Display log selector.
 */
if (count($binary_logs) > 1) {
    echo '<form action="server_binlog.php" method="get">';
    echo PMA_generate_common_hidden_inputs();
    echo '<fieldset><legend>';
    echo $strSelectBinaryLog;
    echo '</legend><select name="log">';
    foreach ($binary_logs as $name) {
        echo '<option value="' . $name . '"' . ($name == $log ? ' selected="selected"' : '') . '>' . $name . '</option>';
    }
    echo '</select>';
    echo '</fieldset>';
    echo '<fieldset class="tblFooters">';
    echo '<input type="submit" value="' . $strGo . '" />';
    echo '</fieldset>';
    echo '</form>';
}


$sql_query = 'SHOW BINLOG EVENTS';
if (!empty($log)) {
    $sql_query .= ' IN \'' . $log . '\'';
}

/**
 * Sends the query and buffers the result
 */
$serverProcesses = PMA_DBI_fetch_result($sql_query);

PMA_showMessage($GLOBALS['strSuccess']);


/**
 * Displays the page
 */
?>
<table border="0" cellpadding="2" cellspacing="1">
<tr>
    <td colspan="6" align="center">
        <a href="./server_binlog.php?<?php echo $url_query . (!empty($log) ? '&amp;log=' . htmlspecialchars($log) : '' ) . (empty($full) ? '&amp;full=1' : ''); ?>"
            title="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>">
                <img src="<?php echo $pmaThemeImage . 's_' . (empty($full) ? 'full' : 'partial'); ?>text.png"
                    width="50" height="20" border="0"
                    alt="<?php echo empty($full) ? $strShowFullQueries : $strTruncateQueries; ?>" /></a></td>
</tr>
<tr>
    <th><?php echo $strBinLogName; ?></th>
    <th><?php echo $strBinLogPosition; ?></th>
    <th><?php echo $strBinLogEventType; ?></th>
    <th><?php echo $strBinLogServerId; ?></th>
    <th><?php echo $strBinLogOriginalPosition; ?></th>
    <th><?php echo $strBinLogInfo; ?></th>
</tr>
<?php
$odd_row = true;
foreach ($serverProcesses as $value) {
    if (empty($full) && PMA_strlen($value['Info']) > $GLOBALS['cfg']['LimitChars']) {
        $value['Info'] = PMA_substr($value['Info'], 0, $GLOBALS['cfg']['LimitChars']) . '...';
    }
    ?>
<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td>&nbsp;<?php echo $value['Log_name']; ?>&nbsp;</td>
    <td align="right">&nbsp;<?php echo $value['Pos']; ?>&nbsp;</td>
    <td>&nbsp;<?php echo $value['Event_type']; ?>&nbsp;</td>
    <td align="right">&nbsp;<?php echo $value['Server_id']; ?>&nbsp;</td>
    <td align="right">&nbsp;<?php echo isset($value['Orig_log_pos']) ? $value['Orig_log_pos'] : $value['End_log_pos']; ?>&nbsp;</td>
    <td>&nbsp;<?php echo htmlspecialchars($value['Info']); ?>&nbsp;</td>
</tr>
    <?php
    $odd_row = !$odd_row;
}
?>
</table>
<?php


/**
 * Sends the footer
 */
require_once './libraries/footer.inc.php';

?>
