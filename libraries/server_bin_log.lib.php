<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server binary log
 *
 * @usedby  server_binlog.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns the html for log selector.
 *
 * @param Array $binary_logs Binary logs file names
 * @param Array $url_params  links parameters
 *
 * @return string
 */
function PMA_getLogSelector($binary_logs, $url_params)
{
    $html = "";
    if (count($binary_logs) > 1) {
        $html .= '<form action="server_binlog.php" method="get">';
        $html .= PMA_URL_getHiddenInputs($url_params);
        $html .= '<fieldset><legend>';
        $html .= __('Select binary log to view');
        $html .= '</legend><select name="log">';
        $full_size = 0;
        foreach ($binary_logs as $each_log) {
            $html .= '<option value="' . $each_log['Log_name'] . '"';
            if ($each_log['Log_name'] == $_REQUEST['log']) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $each_log['Log_name'];
            if (isset($each_log['File_size'])) {
                $full_size += $each_log['File_size'];
                $html .= ' ('
                    . implode(
                        ' ',
                        PMA_Util::formatByteDown(
                            $each_log['File_size'], 3, 2
                        )
                    )
                    . ')';
            }
            $html .= '</option>';
        }
        $html .= '</select> ';
        $html .= count($binary_logs) . ' ' . __('Files') . ', ';
        if ($full_size > 0) {
            $html .= implode(
                ' ', PMA_Util::formatByteDown($full_size)
            );
        }
        $html .= '</fieldset>';
        $html .= '<fieldset class="tblFooters">';
        $html .= '<input type="submit" value="' . __('Go') . '" />';
        $html .= '</fieldset>';
        $html .= '</form>';
    }

    return $html;
}

/**
 * Returns the html for binary log information.
 *
 * @param Array $url_params links parameters
 *
 * @return string
 */
function PMA_getLogInfo($url_params)
{
    /**
     * Need to find the real end of rows?
     */
    if (! isset($_REQUEST['pos'])) {
        $pos = 0;
    } else {
        /* We need this to be a integer */
        $pos = (int) $_REQUEST['pos'];
    }

    $sql_query = 'SHOW BINLOG EVENTS';
    if (! empty($_REQUEST['log'])) {
        $sql_query .= ' IN \'' . $_REQUEST['log'] . '\'';
    }
    $sql_query .= ' LIMIT ' . $pos . ', ' . (int) $GLOBALS['cfg']['MaxRows'];

    /**
     * Sends the query
     */
    $result = $GLOBALS['dbi']->query($sql_query);

    /**
     * prepare some vars for displaying the result table
     */
    // Gets the list of fields properties
    if (isset($result) && $result) {
        $num_rows = $GLOBALS['dbi']->numRows($result);
    } else {
        $num_rows = 0;
    }

    if (empty($_REQUEST['dontlimitchars'])) {
        $dontlimitchars = false;
    } else {
        $dontlimitchars = true;
        $url_params['dontlimitchars'] = 1;
    }

    //html output
    $html  = PMA_Util::getMessage(PMA_Message::success(), $sql_query);
    $html .= '<table cellpadding="2" cellspacing="1" id="binlogTable">'
        . '<thead>'
        . '<tr>'
        . '<td colspan="6" class="center">';

    $html .= PMA_getNavigationRow($url_params, $pos, $num_rows, $dontlimitchars);

    $html .=  '</td>'
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

    $html .= PMA_getAllLogItemInfo($result, $dontlimitchars);

    $html .= '</tbody>'
        . '</table>';

    return $html;
}

/**
 * Returns the html for Navigation Row.
 *
 * @param Array $url_params     Links parameters
 * @param int   $pos            Position to display
 * @param int   $num_rows       Number of results row
 * @param bool  $dontlimitchars Whether limit chars
 *
 * @return string
 */
function PMA_getNavigationRow($url_params, $pos, $num_rows, $dontlimitchars)
{
    $html = "";
    // we do not know how much rows are in the binlog
    // so we can just force 'NEXT' button
    if ($pos > 0) {
        $this_url_params = $url_params;
        if ($pos > $GLOBALS['cfg']['MaxRows']) {
            $this_url_params['pos'] = $pos - $GLOBALS['cfg']['MaxRows'];
        }

        $html .= '<a href="server_binlog.php'
            . PMA_URL_getCommon($this_url_params) . '"';
        if (PMA_Util::showIcons('TableNavigationLinksMode')) {
            $html .= ' title="' . _pgettext('Previous page', 'Previous') . '">';
        } else {
            $html .= '>' . _pgettext('Previous page', 'Previous');
        } // end if... else...
        $html .= ' &lt; </a> - ';
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
    $html .= '<a href="server_binlog.php' . PMA_URL_getCommon($this_url_params)
        . '" title="' . $tempTitle . '">'
        . '<img src="' . $GLOBALS['pmaThemeImage'] . 's_' . $tempImgMode . 'text.png"'
        . 'alt="' . $tempTitle . '" /></a>';

    // we do not now how much rows are in the binlog
    // so we can just force 'NEXT' button
    if ($num_rows >= $GLOBALS['cfg']['MaxRows']) {
        $this_url_params = $url_params;
        $this_url_params['pos'] = $pos + $GLOBALS['cfg']['MaxRows'];
        $html .= ' - <a href="server_binlog.php'
            . PMA_URL_getCommon($this_url_params)
            . '"';
        if (PMA_Util::showIcons('TableNavigationLinksMode')) {
            $html .= ' title="' . _pgettext('Next page', 'Next') . '">';
        } else {
            $html .= '>' . _pgettext('Next page', 'Next');
        } // end if... else...
        $html .= ' &gt; </a>';
    }

    return $html;
}

/**
 * Returns the html for all binary log items.
 *
 * @param resource $result         MySQL Query result
 * @param bool     $dontlimitchars Whether limit chars
 *
 * @return string
 */
function PMA_getAllLogItemInfo($result, $dontlimitchars)
{
    $html = "";
    $odd_row = true;
    while ($value = $GLOBALS['dbi']->fetchAssoc($result)) {
        $html .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">'
            . '<td>&nbsp;' . $value['Log_name'] . '&nbsp;</td>'
            . '<td class="right">&nbsp;' . $value['Pos'] . '&nbsp;</td>'
            . '<td>&nbsp;' . $value['Event_type'] . '&nbsp;</td>'
            . '<td class="right">&nbsp;' . $value['Server_id'] . '&nbsp;</td>'
            . '<td class="right">&nbsp;'
            . (isset($value['Orig_log_pos'])
            ? $value['Orig_log_pos'] : $value['End_log_pos'])
            . '&nbsp;</td>'
            . '<td>&nbsp;' . PMA_Util::formatSql($value['Info'], ! $dontlimitchars)
            . '&nbsp;</td></tr>';

        $odd_row = !$odd_row;
    }
    return $html;
}

?>
