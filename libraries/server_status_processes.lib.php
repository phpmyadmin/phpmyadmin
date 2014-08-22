<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying processes list
 *
 * @usedby  server_status_processes.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Prints html for server status processes
 *
 * @return string
 */
function PMA_getHtmlForServerProcesses()
{
    $notice = PMA_Message::notice(
        __(
            'Note: Enabling the auto refresh here might cause '
            . 'heavy traffic between the web server and the MySQL server.'
        )
    )->getDisplay();
    $retval  = $notice . '<div class="tabLinks">';
    $retval .= '<label>' . __('Refresh rate') . ': ';
    $retval .= PMA_ServerStatusData::getHtmlForRefreshList(
        'refreshRate',
        5,
        Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200)
    );
    $retval .= '</label>';
    $retval .= '<a id="toggleRefresh" href="#">';
    $retval .= PMA_Util::getImage('play.png') . __('Start auto refresh');
    $retval .= '</a>';
    $retval .= '</div>';
    $retval .= PMA_getHtmlForServerProcesslist();
    return $retval;
}

/**
 * Prints Server Process list
 *
 * @return string
 */
function PMA_getHtmlForServerProcesslist()
{
    $url_params = array();

    $show_full_sql = ! empty($_REQUEST['full']);
    if ($show_full_sql) {
        $url_params['full'] = 1;
        $full_text_link = 'server_status_processes.php' . PMA_URL_getCommon(
            array(), 'html', '?'
        );
    } else {
        $full_text_link = 'server_status_processes.php' . PMA_URL_getCommon(
            array('full' => 1)
        );
    }

    // This array contains display name and real column name of each
    // sortable column in the table
    $sortable_columns = array(
        array(
            'column_name' => __('ID'),
            'order_by_field' => 'Id'
        ),
        array(
            'column_name' => __('User'),
            'order_by_field' => 'User'
        ),
        array(
            'column_name' => __('Host'),
            'order_by_field' => 'Host'
        ),
        array(
            'column_name' => __('Database'),
            'order_by_field' => 'db'
        ),
        array(
            'column_name' => __('Command'),
            'order_by_field' => 'Command'
        ),
        array(
            'column_name' => __('Time'),
            'order_by_field' => 'Time'
        ),
        array(
            'column_name' => __('Status'),
            'order_by_field' => 'State'
        ),
        array(
            'column_name' => __('SQL query'),
            'order_by_field' => 'Info'
        )
    );
    $sortableColCount = count($sortable_columns);

    if (PMA_DRIZZLE) {
        $left_str = 'left(p.info, '
            . (int)$GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] . ')';
        $sql_query = "SELECT
                p.id       AS Id,
                p.username AS User,
                p.host     AS Host,
                p.db       AS db,
                p.command  AS Command,
                p.time     AS Time,
                p.state    AS State,"
                . ($show_full_sql ? 's.query' : $left_str )
                . " AS Info FROM data_dictionary.PROCESSLIST p "
                . ($show_full_sql
                ? 'LEFT JOIN data_dictionary.SESSIONS s ON s.session_id = p.id'
                : '');
        if (! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
        ) {
            $sql_query .= ' ORDER BY p.' . $_REQUEST['order_by_field'] . ' '
                 . $_REQUEST['sort_order'];
        }
    } else {
        $sql_query = $show_full_sql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
        if (! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
        ) {
            $sql_query = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` '
                . 'ORDER BY `'
                . $_REQUEST['order_by_field'] . '` ' . $_REQUEST['sort_order'];
        }
    }

    $result = $GLOBALS['dbi']->query($sql_query);

    $retval = '<table id="tableprocesslist" '
        . 'class="data clearfloat noclick sortable">';
    $retval .= '<thead>';
    $retval .= '<tr>';
    $retval .= '<th>' . __('Processes') . '</th>';
    foreach ($sortable_columns as $column) {

        $is_sorted = ! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
            && ($_REQUEST['order_by_field'] == $column['order_by_field']);

        $column['sort_order'] = 'ASC';
        if ($is_sorted && $_REQUEST['sort_order'] === 'ASC') {
            $column['sort_order'] = 'DESC';
        }

        if ($is_sorted) {
            if ($_REQUEST['sort_order'] == 'ASC') {
                $asc_display_style = 'inline';
                $desc_display_style = 'none';
            } elseif ($_REQUEST['sort_order'] == 'DESC') {
                $desc_display_style = 'inline';
                $asc_display_style = 'none';
            }
        }

        $retval .= '<th>';
        $columnUrl = PMA_URL_getCommon($column);
        $retval .= '<a href="server_status_processes.php' . $columnUrl . '" ';
        if ($is_sorted) {
            $retval .= 'onmouseout="$(\'.soimg\').toggle()" '
                . 'onmouseover="$(\'.soimg\').toggle()"';
        }
        $retval .= '>';

        $retval .= $column['column_name'];

        if ($is_sorted) {
            $retval .= '<img class="icon ic_s_desc soimg" alt="'
                . __('Descending') . '" title="" src="themes/dot.gif" '
                . 'style="display: ' . $desc_display_style . '" />';
            $retval .= '<img class="icon ic_s_asc soimg hide" alt="'
                . __('Ascending') . '" title="" src="themes/dot.gif" '
                . 'style="display: ' . $asc_display_style . '" />';
        }

        $retval .= '</a>';

        if (! PMA_DRIZZLE && (0 === --$sortableColCount)) {
            $retval .= '<a href="' . $full_text_link . '">';
            if ($show_full_sql) {
                $retval .= PMA_Util::getImage(
                    's_partialtext.png',
                    __('Truncate Shown Queries')
                );
            } else {
                $retval .= PMA_Util::getImage(
                    's_fulltext.png',
                    __('Show Full Queries')
                );
            }
            $retval .= '</a>';
        }
        $retval .= '</th>';
    }

    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';

    $odd_row = true;
    while ($process = $GLOBALS['dbi']->fetchAssoc($result)) {
        $retval .= PMA_getHtmlForServerProcessItem(
            $process,
            $odd_row,
            $show_full_sql
        );
        $odd_row = ! $odd_row;
    }
    $retval .= '</tbody>';
    $retval .= '</table>';

    return $retval;
}

/**
 * Prints Every Item of Server Process
 *
 * @param Array $process       data of Every Item of Server Process
 * @param bool  $odd_row       display odd row or not
 * @param bool  $show_full_sql show full sql or not
 *
 * @return string
 */
function PMA_getHtmlForServerProcessItem($process, $odd_row, $show_full_sql)
{
    // Array keys need to modify due to the way it has used
    // to display column values
    if (! empty($_REQUEST['order_by_field']) && ! empty($_REQUEST['sort_order']) ) {
        foreach (array_keys($process) as $key) {
            $new_key = ucfirst(mb_strtolower($key));
            if ($new_key !== $key) {
                $process[$new_key] = $process[$key];
                unset($process[$key]);
            }
        }
    }

    $url_params = array(
        'kill' => $process['Id'],
        'ajax_request' => true
    );
    $kill_process = 'server_status_processes.php' . PMA_URL_getCommon($url_params);

    $retval  = '<tr class="' . ($odd_row ? 'odd' : 'even') . '">';
    $retval .= '<td><a class="ajax kill_process" href="' . $kill_process . '">'
        . __('Kill') . '</a></td>';
    $retval .= '<td class="value">' . $process['Id'] . '</td>';
    $retval .= '<td>' . htmlspecialchars($process['User']) . '</td>';
    $retval .= '<td>' . htmlspecialchars($process['Host']) . '</td>';
    $retval .= '<td>' . ((! isset($process['db']) || ! strlen($process['db']))
            ? '<i>' . __('None') . '</i>'
            : htmlspecialchars($process['db'])) . '</td>';
    $retval .= '<td>' . htmlspecialchars($process['Command']) . '</td>';
    $retval .= '<td class="value">' . $process['Time'] . '</td>';
    $processStatusStr = empty($process['State']) ? '---' : $process['State'];
    $retval .= '<td>' . $processStatusStr . '</td>';
    $retval .= '<td>';

    if (empty($process['Info'])) {
        $retval .= '---';
    } else {
        $retval .= PMA_Util::formatSql($process['Info'], ! $show_full_sql);
    }
    $retval .= '</td>';
    $retval .= '</tr>';

    return $retval;
}

?>
