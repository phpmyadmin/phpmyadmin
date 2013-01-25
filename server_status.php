<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * object the server status page: processes, connections and traffic
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/server_common.inc.php';
require_once 'libraries/ServerStatusData.class.php';

/**
 * Replication library
 */
if (PMA_DRIZZLE) {
    $server_master_status = false;
    $server_slave_status = false;
} else {
    include_once 'libraries/replication.inc.php';
    include_once 'libraries/replication_gui.lib.php';
}

$ServerStatusData = new PMA_ServerStatusData();

/**
 * Kills a selected process
 */
if (! empty($_REQUEST['kill'])) {
    if (PMA_DBI_try_query('KILL ' . $_REQUEST['kill'] . ';')) {
        $message = PMA_Message::success(__('Thread %s was successfully killed.'));
    } else {
        $message = PMA_Message::error(
            __(
                'phpMyAdmin was unable to kill thread %s.'
                . ' It probably has already been closed.'
            )
        );
    }
    $message->addParam($_REQUEST['kill']);
}

/**
 * start output
 */
$response = PMA_Response::getInstance();
$response->addHTML('<div>');
$response->addHTML($ServerStatusData->getMenuHtml());
$response->addHTML(getServerTrafficHtml($ServerStatusData));
$response->addHTML('</div>');

exit;

/**
 * Prints server traffic information
 *
 * @param Object $ServerStatusData An instance of the PMA_ServerStatusData class
 *
 * @return string
 */
function getServerTrafficHtml($ServerStatusData)
{
    $hour_factor    = 3600 / $ServerStatusData->status['Uptime'];
    $start_time = PMA_DBI_fetch_value(
        'SELECT UNIX_TIMESTAMP() - ' . $ServerStatusData->status['Uptime']
    );

    $retval  = '<h3>';
    $retval .= sprintf(
        __('Network traffic since startup: %s'),
        implode(
            ' ',
            PMA_Util::formatByteDown(
                $ServerStatusData->status['Bytes_received'] + $ServerStatusData->status['Bytes_sent'],
                3,
                1
            )
        )
    );
    $retval .= '</h3>';
    $retval .= '<p>';
    $retval .= sprintf(
        __('This MySQL server has been running for %1$s. It started up on %2$s.'),
        PMA_Util::timespanFormat($ServerStatusData->status['Uptime']),
        PMA_Util::localisedDate($start_time)
    ) . "\n";
    $retval .= '</p>';

    if ($GLOBALS['server_master_status'] || $GLOBALS['server_slave_status']) {
        $retval .= '<p class="notice">';
        if ($GLOBALS['server_master_status'] && $GLOBALS['server_slave_status']) {
            $retval .= __(
                'This MySQL server works as <b>master</b> and '
                . '<b>slave</b> in <b>replication</b> process.'
            );
        } elseif ($GLOBALS['server_master_status']) {
            $retval .= __(
                'This MySQL server works as <b>master</b> '
                . 'in <b>replication</b> process.'
            );
        } elseif ($GLOBALS['server_slave_status']) {
            $retval .= __(
                'This MySQL server works as <b>slave</b> '
                . 'in <b>replication</b> process.'
            );
        }
        $retval .= ' ';
        $retval .= __(
            'For further information about replication status on the server, '
            . 'please visit the <a href="#replication">replication section</a>.'
        );
        $retval .= '</p>';
    }

    /*
     * if the server works as master or slave in replication process,
     * display useful information
     */
    if ($GLOBALS['server_master_status'] || $GLOBALS['server_slave_status']) {
        $retval .= '<hr class="clearfloat" />';
        $retval .= '<h3><a name="replication">';
        $retval .= __('Replication status');
        $retval .= '</a></h3>';
        foreach ($GLOBALS['replication_types'] as $type) {
            if (isset(${"server_{$type}_status"}) && ${"server_{$type}_status"}) {
                PMA_replication_print_status_table($type);
            }
        }
    }

    $retval .= '<table id="serverstatustraffic" class="data noclick">';
    $retval .= '<thead>';
    $retval .= '<tr>';
    $retval .= '<th colspan="2">';
    $retval .= __('Traffic') . '&nbsp;';
    $retval .=  PMA_Util::showHint(
        __(
            'On a busy server, the byte counters may overrun, so those statistics '
            . 'as reported by the MySQL server may be incorrect.'
        )
    );
    $retval .= '</th>';
    $retval .= '<th>&oslash; ' . __('per hour') . '</th>';
    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';
    $retval .= '<tr class="odd">';
    $retval .= '<th class="name">' . __('Received') . '</th>';
    $retval .= '<td class="value">';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            $ServerStatusData->status['Bytes_received'], 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            $ServerStatusData->status['Bytes_received'] * $hour_factor, 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '</tr>';
    $retval .= '<tr class="even">';
    $retval .= '<th class="name">' . __('Sent') . '</th>';
    $retval .= '<td class="value">';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            $ServerStatusData->status['Bytes_sent'], 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '<td class="value"><?php echo';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            $ServerStatusData->status['Bytes_sent'] * $hour_factor, 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '</tr>';
    $retval .= '<tr class="odd">';
    $retval .= '<th class="name">' . __('Total') . '</th>';
    $retval .= '<td class="value">';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            $ServerStatusData->status['Bytes_received'] + $ServerStatusData->status['Bytes_sent'], 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= implode(
        ' ',
        PMA_Util::formatByteDown(
            ($ServerStatusData->status['Bytes_received'] + $ServerStatusData->status['Bytes_sent'])
            * $hour_factor, 3, 1
        )
    );
    $retval .= '</td>';
    $retval .= '</tr>';
    $retval .= '</tbody>';
    $retval .= '</table>';

    $retval .= '<table id="serverstatusconnections" class="data noclick">';
    $retval .= '<thead>';
    $retval .= '<tr>';
    $retval .= '<th colspan="2">' . __('Connections') . '</th>';
    $retval .= '<th>&oslash; ' . __('per hour') . '</th>';
    $retval .= '<th>%</th>';
    $retval .= '</tr>';
    $retval .= '</thead>';
    $retval .= '<tbody>';
    $retval .= '<tr class="odd">';
    $retval .= '<th class="name">' . __('max. concurrent connections') . '</th>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Max_used_connections'], 0
    );
    $retval .= '</td>';
    $retval .= '<td class="value">--- </td>';
    $retval .= '<td class="value">--- </td>';
    $retval .= '</tr>';
    $retval .= '<tr class="even">';
    $retval .= '<th class="name">' . __('Failed attempts') . '</th>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Aborted_connects'], 4, 1, true
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Aborted_connects'] * $hour_factor, 4, 2, true
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    if ($ServerStatusData->status['Connections'] > 0) {
        $retval .= PMA_Util::formatNumber(
            $ServerStatusData->status['Aborted_connects'] * 100 / $ServerStatusData->status['Connections'],
            0, 2, true
        );
        $retval .= '%';
    } else {
        $retval .= '--- ';
    }
    $retval .= '</td>';
    $retval .= '</tr>';
    $retval .= '<tr class="odd">';
    $retval .= '<th class="name">' . __('Aborted') . '</th>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Aborted_clients'], 4, 1, true
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Aborted_clients'] * $hour_factor, 4, 2, true
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    if ($ServerStatusData->status['Connections'] > 0) {
        $retval .= PMA_Util::formatNumber(
            $ServerStatusData->status['Aborted_clients'] * 100 / $ServerStatusData->status['Connections'],
            0, 2, true
        );
        $retval .= '%';
    } else {
        $retval .= '--- ';
    }
    $retval .= '</td>';
    $retval .= '</tr>';
    $retval .= '<tr class="even">';
    $retval .= '<th class="name">' . __('Total') . '</th>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Connections'], 4, 0
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(
        $ServerStatusData->status['Connections'] * $hour_factor, 4, 2
    );
    $retval .= '</td>';
    $retval .= '<td class="value">';
    $retval .= PMA_Util::formatNumber(100, 0, 2);
    $retval .= '%</td>';
    $retval .= '</tr>';
    $retval .= '</tbody>';
    $retval .= '</table>';

    $url_params = array();

    $show_full_sql = ! empty($_REQUEST['full']);
    if ($show_full_sql) {
        $url_params['full'] = 1;
        $full_text_link = 'server_status.php' . PMA_generate_common_url(
            array(), 'html', '?'
        );
    } else {
        $full_text_link = 'server_status.php' . PMA_generate_common_url(
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
    $sortable_columns_count = count($sortable_columns);

    if (PMA_DRIZZLE) {
        $sql_query = "SELECT
                p.id       AS Id,
                p.username AS User,
                p.host     AS Host,
                p.db       AS db,
                p.command  AS Command,
                p.time     AS Time,
                p.state    AS State,
                " . ($show_full_sql ? 's.query' : 'left(p.info, ' . (int)$GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] . ')') . " AS Info
            FROM data_dictionary.PROCESSLIST p
                " . ($show_full_sql ? 'LEFT JOIN data_dictionary.SESSIONS s ON s.session_id = p.id' : '');
        if (! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
        ) {
            $sql_query .= ' ORDER BY p.' . $_REQUEST['order_by_field'] . ' ' . $_REQUEST['sort_order'];
        }
    } else {
        $sql_query = $show_full_sql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
        if (! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
        ) {
            $sql_query = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ORDER BY `'
                . $_REQUEST['order_by_field'] . '` ' . $_REQUEST['sort_order'];
        }
    }

    $result = PMA_DBI_query($sql_query);

    /**
     * Displays the page
     */
    $retval .= '<table id="tableprocesslist" class="data clearfloat noclick sortable">';
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
        $retval .= '<a href="server_status.php' . PMA_generate_common_url($column) . '" ';
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

        if (! PMA_DRIZZLE && (0 === --$sortable_columns_count)) {
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
    while ($process = PMA_DBI_fetch_assoc($result)) {

        // Array keys need to modify due to the way it has used
        // to display column values
        if (! empty($_REQUEST['order_by_field'])
            && ! empty($_REQUEST['sort_order'])
        ) {
            foreach (array_keys($process) as $key) {
                $new_key = ucfirst(strtolower($key));
                $process[$new_key] = $process[$key];
                unset($process[$key]);
            }
        }

        $url_params['kill'] = $process['Id'];
        $kill_process = 'server_status.php' . PMA_generate_common_url($url_params);

        $retval .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        $retval .= '<td><a href="' . $kill_process . '">' . __('Kill') . '</a></td>';
        $retval .= '<td class="value">' . $process['Id'] . '</td>';
        $retval .= '<td>' . $process['User'] . '</td>';
        $retval .= '<td>' . $process['Host'] . '</td>';
        $retval .= '<td>' . ((! isset($process['db']) || ! strlen($process['db'])) ? '<i>' . __('None') . '</i>' : $process['db']) . '</td>';
        $retval .= '<td>' . $process['Command'] . '</td>';
        $retval .= '<td class="value">' . $process['Time'] . '</td>';
        $retval .= '<td>' . (empty($process['State']) ? '---' : $process['State']) . '</td>';
        $retval .= '<td>';

        if (empty($process['Info'])) {
            $retval .= '---';
        } else {
            if (! $show_full_sql && strlen($process['Info']) > $GLOBALS['cfg']['MaxCharactersInDisplayedSQL']) {
                $retval .= htmlspecialchars(substr($process['Info'], 0, $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'])) . '[...]';
            } else {
                $retval .= PMA_SQP_formatHtml(PMA_SQP_parse($process['Info']));
            }
        }
        $retval .= '</td>';
        $retval .= '</tr>';
        $odd_row = ! $odd_row;
    }
    $retval .= '</tbody>';
    $retval .= '</table>';

    return $retval;
}

?>
