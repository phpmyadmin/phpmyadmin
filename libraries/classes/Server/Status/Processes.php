<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying processes list
 *
 * @usedby  server_status_processes.php
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\Message;
use PhpMyAdmin\Server\Status\Data;
use PhpMyAdmin\Util;
use PhpMyAdmin\Url;

/**
 * PhpMyAdmin\Server\Status\Processes class
 *
 * @package PhpMyAdmin
 */
class Processes
{
    /**
     * Prints html for auto refreshing processes list
     *
     * @return string
     */
    public static function getHtmlForProcessListAutoRefresh()
    {
        $notice = Message::notice(
            __(
                'Note: Enabling the auto refresh here might cause '
                . 'heavy traffic between the web server and the MySQL server.'
            )
        )->getDisplay();
        $retval  = $notice . '<div class="tabLinks">';
        $retval .= '<label>' . __('Refresh rate') . ': ';
        $retval .= Data::getHtmlForRefreshList(
            'refreshRate',
            5,
            Array(2, 3, 4, 5, 10, 20, 40, 60, 120, 300, 600, 1200)
        );
        $retval .= '</label>';
        $retval .= '<a id="toggleRefresh" href="#">';
        $retval .= Util::getImage('play') . __('Start auto refresh');
        $retval .= '</a>';
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Prints Server Process list
     *
     * @return string
     */
    public static function getHtmlForServerProcesslist()
    {
        $url_params = array();

        $show_full_sql = ! empty($_POST['full']);
        if ($show_full_sql) {
            $url_params['full'] = 1;
            $full_text_link = 'server_status_processes.php' . Url::getCommon(
                array(), '?'
            );
        } else {
            $full_text_link = 'server_status_processes.php' . Url::getCommon(
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
                'column_name' => __('Progress'),
                'order_by_field' => 'Progress'
            ),
            array(
                'column_name' => __('SQL query'),
                'order_by_field' => 'Info'
            )
        );
        $sortableColCount = count($sortable_columns);

        $sql_query = $show_full_sql
            ? 'SHOW FULL PROCESSLIST'
            : 'SHOW PROCESSLIST';
        if ((! empty($_POST['order_by_field'])
            && ! empty($_POST['sort_order']))
            || (! empty($_POST['showExecuting']))
        ) {
            $sql_query = 'SELECT * FROM `INFORMATION_SCHEMA`.`PROCESSLIST` ';
        }
        if (! empty($_POST['showExecuting'])) {
            $sql_query .= ' WHERE state != "" ';
        }
        if (!empty($_POST['order_by_field']) && !empty($_POST['sort_order'])) {
            $sql_query .= ' ORDER BY '
                . Util::backquote($_POST['order_by_field'])
                . ' ' . $_POST['sort_order'];
        }

        $result = $GLOBALS['dbi']->query($sql_query);

        $retval = '<div class="responsivetable">';
        $retval .= '<table id="tableprocesslist" '
            . 'class="data clearfloat noclick sortable">';
        $retval .= '<thead>';
        $retval .= '<tr>';
        $retval .= '<th>' . __('Processes') . '</th>';
        foreach ($sortable_columns as $column) {

            $is_sorted = ! empty($_POST['order_by_field'])
                && ! empty($_POST['sort_order'])
                && ($_POST['order_by_field'] == $column['order_by_field']);

            $column['sort_order'] = 'ASC';
            if ($is_sorted && $_POST['sort_order'] === 'ASC') {
                $column['sort_order'] = 'DESC';
            }
            if (isset($_POST['showExecuting'])) {
                $column['showExecuting'] = 'on';
            }

            $retval .= '<th>';
            $columnUrl = Url::getCommon($column);
            $retval .= '<a href="server_status_processes.php' . $columnUrl . '" class="sortlink">';

            $retval .= $column['column_name'];

            if ($is_sorted) {
                $asc_display_style = 'inline';
                $desc_display_style = 'none';
                if ($_POST['sort_order'] === 'DESC') {
                    $desc_display_style = 'inline';
                    $asc_display_style = 'none';
                }
                $retval .= '<img class="icon ic_s_desc soimg" alt="'
                    . __('Descending') . '" title="" src="themes/dot.gif" '
                    . 'style="display: ' . $desc_display_style . '" />';
                $retval .= '<img class="icon ic_s_asc soimg hide" alt="'
                    . __('Ascending') . '" title="" src="themes/dot.gif" '
                    . 'style="display: ' . $asc_display_style . '" />';
            }

            $retval .= '</a>';

            if (0 === --$sortableColCount) {
                $retval .= '<a href="' . $full_text_link . '">';
                if ($show_full_sql) {
                    $retval .= Util::getImage('s_partialtext',
                        __('Truncate Shown Queries'), ['class' => 'icon_fulltext']);
                } else {
                    $retval .= Util::getImage('s_fulltext',
                        __('Show Full Queries'), ['class' => 'icon_fulltext']);
                }
                $retval .= '</a>';
            }
            $retval .= '</th>';
        }

        $retval .= '</tr>';
        $retval .= '</thead>';
        $retval .= '<tbody>';

        while ($process = $GLOBALS['dbi']->fetchAssoc($result)) {
            $retval .= self::getHtmlForServerProcessItem(
                $process,
                $show_full_sql
            );
        }
        $retval .= '</tbody>';
        $retval .= '</table>';
        $retval .= '</div>';

        return $retval;
    }

    /**
     * Returns the html for the list filter
     *
     * @return string
     */
    public static function getHtmlForProcessListFilter()
    {
        $showExecuting = '';
        if (! empty($_POST['showExecuting'])) {
            $showExecuting = ' checked="checked"';
        }

        $url_params = array(
            'ajax_request' => true,
            'full' => (isset($_POST['full']) ? $_POST['full'] : ''),
            'column_name' => (isset($_POST['column_name']) ? $_POST['column_name'] : ''),
            'order_by_field'
                => (isset($_POST['order_by_field']) ? $_POST['order_by_field'] : ''),
            'sort_order' => (isset($_POST['sort_order']) ? $_POST['sort_order'] : ''),
        );

        $retval  = '';
        $retval .= '<fieldset id="tableFilter">';
        $retval .= '<legend>' . __('Filters') . '</legend>';
        $retval .= '<form action="server_status_processes.php" method="post">';
        $retval .= Url::getHiddenInputs($url_params);
        $retval .= '<input type="submit" value="' . __('Refresh') . '" />';
        $retval .= '<div class="formelement">';
        $retval .= '<input' . $showExecuting . ' type="checkbox" name="showExecuting"'
            . ' id="showExecuting" class="autosubmit"/>';
        $retval .= '<label for="showExecuting">';
        $retval .= __('Show only active');
        $retval .= '</label>';
        $retval .= '</div>';
        $retval .= '</form>';
        $retval .= '</fieldset>';

        return $retval;
    }

    /**
     * Prints Every Item of Server Process
     *
     * @param array $process       data of Every Item of Server Process
     * @param bool  $show_full_sql show full sql or not
     *
     * @return string
     */
    public static function getHtmlForServerProcessItem(array $process, $show_full_sql)
    {
        // Array keys need to modify due to the way it has used
        // to display column values
        if ((! empty($_POST['order_by_field']) && ! empty($_POST['sort_order']))
            || (! empty($_POST['showExecuting']))
        ) {
            foreach (array_keys($process) as $key) {
                $new_key = ucfirst(mb_strtolower($key));
                if ($new_key !== $key) {
                    $process[$new_key] = $process[$key];
                    unset($process[$key]);
                }
            }
        }

        $retval  = '<tr>';
        $retval .= '<td><a class="ajax kill_process" href="server_status_processes.php"'
            . ' data-post="' . Url::getCommon(['kill' => $process['Id']], '') . '">'
            . __('Kill') . '</a></td>';
        $retval .= '<td class="value">' . $process['Id'] . '</td>';
        $retval .= '<td>' . htmlspecialchars($process['User']) . '</td>';
        $retval .= '<td>' . htmlspecialchars($process['Host']) . '</td>';
        $retval .= '<td>' . ((! isset($process['db'])
                || strlen($process['db']) === 0)
                ? '<i>' . __('None') . '</i>'
                : htmlspecialchars($process['db'])) . '</td>';
        $retval .= '<td>' . htmlspecialchars($process['Command']) . '</td>';
        $retval .= '<td class="value">' . $process['Time'] . '</td>';
        $processStatusStr = empty($process['State']) ? '---' : $process['State'];
        $retval .= '<td>' . $processStatusStr . '</td>';
        $processProgress = empty($process['Progress']) ? '---' : $process['Progress'];
        $retval .= '<td>' . $processProgress . '</td>';
        $retval .= '<td>';

        if (empty($process['Info'])) {
            $retval .= '---';
        } else {
            $retval .= Util::formatSql($process['Info'], ! $show_full_sql);
        }
        $retval .= '</td>';
        $retval .= '</tr>';

        return $retval;
    }
}
