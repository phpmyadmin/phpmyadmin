<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Print view of a database
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/db_printview.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->enablePrintView();

PMA_Util::checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'db_sql.php' . PMA_URL_getCommon(array('db' => $db));

/**
 * Settings for relations stuff
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * If there is at least one table, displays the printer friendly view, else
 * an error message
 */
$tables = $GLOBALS['dbi']->getTablesFull($db);
$num_tables = count($tables);

$response->addHTML('<br />');

// 1. No table
if ($num_tables == 0) {
    $response->addHTML(__('No tables found in database.'));
} else {
    $table_html = '<table>';
    $table_html .= '<thead>';
    $table_html .= '<tr>';
    $table_html .= '<th>' . __('Table') . '</th>';
    $table_html .= '<th>' . __('Rows') . '</th>';
    $table_html .= '<th>' . __('Type') . '</th>';
    if ($cfg['ShowStats']) {
        $table_html .= '<th>' . __('Size') . '</th>';
    }
    $table_html .= '<th>' . __('Comments') . '</th>';
    $table_html .= '</tr>';
    $table_html .= '</thead>';

    $table_html .= '<tbody>';

    $table_html .= '<tr>';

    $i = 0;
    $j = 0;
    $k = 0;

    $rows = json_decode($_REQUEST['rows_sent'], true);
    $odd_row = true;
    $table_name = '';
    $count_tables = count($rows);

    $column_heads_orig = json_decode($_REQUEST['columns_sent'], true);
    // Add 6 to the count because 6 'Action' buttons
    $column_count = count($column_heads_orig) + 6;

    foreach ($rows as $row) {
        $table_html .= '<tr class="' .  ($odd_row ? 'odd' : 'even') . '">';

        foreach ($row as $value) {
            // 0 - Checkbox - SKIP
            // 1 - Table Name
            // 2-8 - Action Button Links - SKIP
            // 9 - Rows
            // 10 - Engine Type
            // 11 - Collation - SKIP
            // 12 - Size
            // 13 - Overhead - SKIP
            // 14 - Comments
            // 15-17 - Creation/ Last Update/ Last Check time
            if ($j == 1) {
                $table_html .= '<th>';
                $table_html .= htmlspecialchars($value);
                $table_html .= '</th>';

                $table_name = $value;

                if (PMA_Table::isMerge($db, $value)) {
                    $merged_size = true;
                } else {
                    $merged_size = false;
                }
                $j++;
            } elseif ($j == 0 || ($j >=2 && $j <= 8)) {
                $j++;
                continue;
            } elseif ($j == 9) {
                if (isset($value)) {
                    $table_html .= '<td class="right">';
                    if ($merged_size) {
                        $table_html .= '<i>';
                        $table_html .= PMA_Util::formatNumber($value, 0);
                        $table_html .= '</i>';
                    } else {
                        $table_html .= PMA_Util::formatNumber($value, 0);
                    }
                    $table_html .= '</td>';
                    $engine_to_be_printed = true;
                } else {
                    $engine_to_be_printed = false;
                }
                $j++;
            } elseif ($j == 10) {
                if ($engine_to_be_printed) {
                    $table_html .= '<td class="nowrap">';
                    $table_html .= $value;
                    $table_html .= '</td>';
                } else {
                    $table_html .= '<td colspan="3" class="center">';
                    if (! PMA_Table::isView($db, $table_name)) {
                        $table_html .= __('in use');
                    }
                    $table_html .= '</td>';
                }
                $j++;
            } elseif ($j == 12) {
                $table_html .= '<td>';
                $table_html .= $value;
                $table_html .= '</td>';
                $j++;
            } elseif ($j == 14) {
                $table_html .= '<td>';
                if (! empty($value)) {
                    $table_html .= htmlspecialchars($value);
                    $needs_break = '<br />';
                } else {
                    $needs_break = '';
                }
                $j++;
                if ($j == $count_tables) {
                    $table_html .= '</td>';
                    $table_html .= '</tr>';
                    break;
                }
            } elseif (($j == 17 || $j == 16 || $j == 15) && (! empty($value))) {
                if ($j == 15) {
                    $table_html .= $needs_break;
                    $table_html .= '<table width="100%">';
                }

                if ($value !== '-') {
                    $table_html .= PMA_getHtmlForOneDate(
                        __($column_heads_orig[$j - 6]),
                        $value
                    );
                }

                $j++;
                if ($j >= $column_count) {
                    $table_html .= '</table>';
                    $table_html .= '</td>';
                    $table_html .= '</tr>';
                    break;
                }
            } else {
                $j++;
            }
        }
        $j = 0;
        $odd_row = !($odd_row);
        $table_html .= '</tr>';
    }

    $summary_row = json_decode($_REQUEST['summary'], true);
    $summary_html = '<tr>';

    $s = 0;

    foreach ($summary_row as $value) {
        // 0 - blank - SKIP
        // 1 - Number of Tables
        // 2 - Sum Label - SKIP
        // 3, 4, 6 - Rows, Engine, Size
        // 7 - Blank
        if ($s == 0 || $s == 2 || $s == 5) {
            $s++;
            continue;
        } elseif ($s == 1) {
            $summary_html .= '<th>';
            $summary_html .= $value;
            $summary_html .= '</th>';
            $s++;
        } elseif ($s >= 3 && $s <= 6 && $s != 5) {
            $summary_html .= '<th>';
            $summary_html .= $value;
            $summary_html .= '</th>';
            $s++;
        } else {
            $summary_html .= '<th></th>';
            $s++;
            if ($s >= 7) {
                break;
            }
        }
    }

    $summary_html .= '</tr>';
    $table_html .= $summary_html;
    $table_html .= '</tbody>';
    $table_html .= '</table>';
    $response->addHTML($table_html);

}
/**
 * Displays the footer
 */
$response->addHTML(PMA_Util::getButton());