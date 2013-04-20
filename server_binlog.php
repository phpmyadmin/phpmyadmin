<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display the binary logs and the content of the selected
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work, provides $binary_logs
 */
require_once 'libraries/server_common.inc.php';

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

if (! isset($_REQUEST['log'])
    || ! array_key_exists($_REQUEST['log'], $binary_logs)
) {
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
   . PMA_Util::getImage('s_tbl.png')
   . '    ' . __('Binary log') . "\n"
   . '</h2>' . "\n";

/**
 * Display log selector.
 */
if (count($binary_logs) > 1) {
    echo '<form action="server_binlog.php" method="get">';
    echo PMA_generate_common_hidden_inputs($url_params);
    echo '<fieldset><legend>';
    echo __('Select binary log to view');
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
            echo ' ('
                . implode(
                    ' ',
                    PMA_Util::formatByteDown(
                        $each_log['File_size'], 3, 2
                    )
                )
                . ')';
        }
        echo '</option>';
    }
    echo '</select> ';
    echo count($binary_logs) . ' ' . __('Files') . ', ';
    if ($full_size > 0) {
        echo implode(
            ' ', PMA_Util::formatByteDown($full_size)
        );
    }
    echo '</fieldset>';
    echo '<fieldset class="tblFooters">';
    echo '<input type="submit" value="' . __('Go') . '" />';
    echo '</fieldset>';
    echo '</form>';
}

echo PMA_Util::getMessage(PMA_Message::success());

/**
 * Displays the page
 */
echo '<table cellpadding="2" cellspacing="1">'
    . '<thead>'
    . '<tr>'
    . '<td colspan="6" class="center">';

// we do not now how much rows are in the binlog
// so we can just force 'NEXT' button
if ($pos > 0) {
    $this_url_params = $url_params;
    if ($pos > $GLOBALS['cfg']['MaxRows']) {
        $this_url_params['pos'] = $pos - $GLOBALS['cfg']['MaxRows'];
    }

    echo '<a href="server_binlog.php'
        . PMA_generate_common_url($this_url_params) . '"';
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo ' title="' . _pgettext('Previous page', 'Previous') . '">';
    } else {
        echo '>' . _pgettext('Previous page', 'Previous');
    } // end if... else...
    echo ' &lt; </a> - ';
}

$this_url_params = $url_params;
if ($pos > 0) {
    $this_url_params['pos'] = $pos;
}
if ($dontlimitchars) {
    unset($this_url_params['dontlimitchars']);
    $tempTitle = __('Truncate Shown Queries');
    $tempImgMode = 'partial';
} else {
    $this_url_params['dontlimitchars'] = 1;
    $tempTitle = __('Show Full Queries');
    $tempImgMode = 'full';
}
echo '<a href="server_binlog.php' . PMA_generate_common_url($this_url_params)
    . '" title="' . $tempTitle . '">'
    . '<img src="' .$pmaThemeImage . 's_' . $tempImgMode . 'text.png"'
    . 'alt="' . $tempTitle . '" /></a>';

// we do not now how much rows are in the binlog
// so we can just force 'NEXT' button
if ($num_rows >= $GLOBALS['cfg']['MaxRows']) {
    $this_url_params = $url_params;
    $this_url_params['pos'] = $pos + $GLOBALS['cfg']['MaxRows'];
    echo ' - <a href="server_binlog.php' . PMA_generate_common_url($this_url_params)
        . '"';
    if ($GLOBALS['cfg']['NavigationBarIconic']) {
        echo ' title="' . _pgettext('Next page', 'Next') . '">';
    } else {
        echo '>' . _pgettext('Next page', 'Next');
    } // end if... else...
    echo ' &gt; </a>';
}

echo  '</td>'
    . '</tr>'
    . '<tr>'
    . '<th>' . __('Log name') . '</th>'
    . '<th>' . __('Position') . '</th>'
    . '<th>' . __('Event type') . '</th>'
    . '<th>' . __('Server ID') . '</th>'
    . '<th>' . __('Original position') . '</th>'
    . '<th>' . __('Information') . '</th>'
    . '</tr>'
    . '</thead>'
    . '<tbody>';

$odd_row = true;
while ($value = PMA_DBI_fetch_assoc($result)) {
    if (! $dontlimitchars
        && PMA_strlen($value['Info']) > $GLOBALS['cfg']['LimitChars']
    ) {
        $value['Info'] = PMA_substr(
            $value['Info'], 0, $GLOBALS['cfg']['LimitChars']
        ) . '...';
    }

    echo '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">'
        . '<td>&nbsp;' . $value['Log_name'] . '&nbsp;</td>'
        . '<td class="right">&nbsp;' . $value['Pos'] . '&nbsp;</td>'
        . '<td>&nbsp;' . $value['Event_type'] . '&nbsp;</td>'
        . '<td class="right">&nbsp;' . $value['Server_id'] . '&nbsp;</td>'
        . '<td class="right">&nbsp;'
        . (isset($value['Orig_log_pos'])
        ? $value['Orig_log_pos'] : $value['End_log_pos'])
        . '&nbsp;</td>'
        . '<td>&nbsp;' . htmlspecialchars($value['Info']) . '&nbsp;</td>'
        . '</tr>';

    $odd_row = !$odd_row;
}
echo '</tbody>'
    . '</table>';
