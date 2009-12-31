<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display the binary logs and the content of the selected
 *
 * @uses    $cfg['MainPageIconic']
 * @uses    $cfg['NavigationBarIconic']
 * @uses    $cfg['MaxRows']
 * @uses    $cfg['LimitChars']
 * @uses    $pmaThemeImage
 * @uses    $GLOBALS['strBinaryLog']
 * @uses    $GLOBALS['strGo']
 * @uses    $GLOBALS['strTruncateQueries']
 * @uses    $GLOBALS['strShowFullQueries']
 * @uses    $GLOBALS['strBinLogName']
 * @uses    $GLOBALS['strBinLogPosition']
 * @uses    $GLOBALS['strBinLogEventType']
 * @uses    $GLOBALS['strBinLogServerId']
 * @uses    $GLOBALS['strBinLogOriginalPosition']
 * @uses    $GLOBALS['strBinLogInfo']
 * @uses    $GLOBALS['strFiles']
 * @uses    $GLOBALS['strPrevious']
 * @uses    $GLOBALS['strNext']
 * @uses    $binary_logs
 * @uses    PMA_generate_common_hidden_inputs()
 * @uses    PMA_generate_common_url()
 * @uses    PMA_formatByteDown()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_strlen()
 * @uses    PMA_substr()
 * @uses    $_REQUEST['pos']
 * @uses    $_REQUEST['log']
 * @uses    $_REQUEST['dontlimitchars']
 * @uses    count()
 * @uses    array_key_exists()
 * @uses    implode()
 * @uses    htmlspecialchars()
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 *
 */
require_once './libraries/common.inc.php';

/**
 * Does the common work, provides $binary_logs
 */
require_once './libraries/server_common.inc.php';

/**
 * Displays the links
 */
require_once './libraries/server_links.inc.php';

$url_params = array();

/**
 * Need to find the real end of rows?
 */
if (! isset($_REQUEST['pos'])) {
    $pos = 0;
} else {
    /* We need this to be a integer */
    $pos = (int) $_REQUEST['pos'];
}

if (! isset($_REQUEST['log']) || ! array_key_exists($_REQUEST['log'], $binary_logs)) {
    $_REQUEST['log'] = '';
} else {
    $url_params['log'] = $_REQUEST['log'];
}

$sql_query = 'SHOW BINLOG EVENTS';
if (! empty($_REQUEST['log'])) {
    $sql_query .= ' IN \'' . $_REQUEST['log'] . '\'';
}
if ($GLOBALS['cfg']['MaxRows'] !== 'all') {
    $sql_query .= ' LIMIT ' . $pos . ', ' . (int) $GLOBALS['cfg']['MaxRows'];
}

/**
 * Sends the query
 */
$result = PMA_DBI_query($sql_query);

/**
 * prepare some vars for displaying the result table
 */
// Gets the list of fields properties
if (isset($result) && $result) {
    $num_rows = PMA_DBI_num_rows($result);
} else {
    $num_rows = 0;
}

if (empty($_REQUEST['dontlimitchars'])) {
    $dontlimitchars = false;
} else {
    $dontlimitchars = true;
    $url_params['dontlimitchars'] = 1;
}

/**
 * Displays the sub-page heading
 */
echo '<h2>' . "\n"
   . ($GLOBALS['cfg']['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" alt="" />' : '')
   . '    ' . $GLOBALS['strBinaryLog'] . "\n"
   . '</h2>' . "\n";

/**
 * Display log selector.
 */
if (count($binary_logs) > 1) {
    echo '<form action="server_binlog.php" method="get">';
    echo PMA_generate_common_hidden_inputs($url_params);
    echo '<fieldset><legend>';
    echo $GLOBALS['strSelectBinaryLog'];
    echo '</legend><select name="log">';
    $full_size = 0;
    foreach ($binary_logs as $each_log) {
        echo '<option value="' . $each_log['Log_name'] . '"';
        if ($each_log['Log_name'] == $_REQUEST['log']) {
            echo ' selected="selected"';
        }
        echo '>' . $each_log['Log_name'];
        if (isset($each_log['File_size'])) {
            $full_size += $each_log['File_size'];
            echo ' (' . implode(' ', PMA_formatByteDown($each_log['File_size'], 3, 2)) . ')';
        }
        echo '</option>';
    }
    echo '</select> ';
    echo count($binary_logs) . ' ' . $GLOBALS['strFiles'] . ', ';
    if ($full_size > 0) {
        echo implode(' ', PMA_formatByteDown($full_size));
    }
    echo '</fieldset>';
    echo '<fieldset class="tblFooters">';
    echo '<input type="submit" value="' . $GLOBALS['strGo'] . '" />';
    echo '</fieldset>';
    echo '</form>';
}

PMA_Message::success()->display();

/**
 * Displays the page
 */
?>
<table border="0" cellpadding="2" cellspacing="1">
<thead>
<tr>
    <td colspan="6" align="center">
<?php
// we do not now how much rows are in the binlog
// so we can just force 'NEXT' button
if ($pos > 0) {
    $this_url_params = $url_params;
    if ($pos > $GLOBALS['cfg']['MaxRows']) {
        $this_url_params['pos'] = $pos - $GLOBALS['cfg']['MaxRows'];
    }

    echo '<a href="./server_binlog.php' . PMA_generate_common_url($this_url_params) . '"';
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo ' title="' . $GLOBALS['strPrevious'] . '">';
    } else {
        echo '>' . $GLOBALS['strPrevious'];
    } // end if... else...
    echo ' &lt; </a> - ';
}

$this_url_params = $url_params;
if ($pos > 0) {
    $this_url_params['pos'] = $pos;
}
if ($dontlimitchars) {
    unset($this_url_params['dontlimitchars']);
    ?>
        <a href="./server_binlog.php<?php echo PMA_generate_common_url($this_url_params); ?>"
            title="<?php $GLOBALS['strTruncateQueries']; ?>">
                <img src="<?php echo $pmaThemeImage; ?>s_partialtext.png"
                    width="50" height="20" border="0"
                    alt="<?php echo $GLOBALS['strTruncateQueries']; ?>" /></a>
    <?php
} else {
    $this_url_params['dontlimitchars'] = 1;
    ?>
        <a href="./server_binlog.php<?php echo PMA_generate_common_url($this_url_params); ?>"
            title="<?php $GLOBALS['strShowFullQueries']; ?>">
                <img src="<?php echo $pmaThemeImage; ?>s_fulltext.png"
                    width="50" height="20" border="0"
                    alt="<?php echo $GLOBALS['strShowFullQueries']; ?>" /></a>
    <?php
}
// we do not now how much rows are in the binlog
// so we can just force 'NEXT' button
if ($num_rows >= $GLOBALS['cfg']['MaxRows']) {
    $this_url_params = $url_params;
    $this_url_params['pos'] = $pos + $GLOBALS['cfg']['MaxRows'];
    echo ' - <a href="./server_binlog.php' . PMA_generate_common_url($this_url_params) . '"';
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo ' title="' . $GLOBALS['strNext'] . '">';
    } else {
        echo '>' . $GLOBALS['strNext'];
    } // end if... else...
    echo ' &gt; </a>';
}
?>
    </td>
</tr>
<tr>
    <th><?php echo $GLOBALS['strBinLogName']; ?></th>
    <th><?php echo $GLOBALS['strBinLogPosition']; ?></th>
    <th><?php echo $GLOBALS['strBinLogEventType']; ?></th>
    <th><?php echo $GLOBALS['strBinLogServerId']; ?></th>
    <th><?php echo $GLOBALS['strBinLogOriginalPosition']; ?></th>
    <th><?php echo $GLOBALS['strBinLogInfo']; ?></th>
</tr>
</thead>
<tbody>
<?php
$odd_row = true;
while ($value = PMA_DBI_fetch_assoc($result)) {
    if (! $dontlimitchars && PMA_strlen($value['Info']) > $GLOBALS['cfg']['LimitChars']) {
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
</tbody>
</table>
<?php


/**
 * Sends the footer
 */
require_once './libraries/footer.inc.php';

?>
