<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Print view of a SQL Result
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/sql.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->enablePrintView();

PMA_Util::checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'sql.php' . PMA_URL_getCommon(array('db' => $db, 'table' => $table));

/**
 * Settings for relations stuff
 */
$cfgRelation = PMA_getRelationsParam();

$sql_query = $_REQUEST['sql_query_sent'];
$num_rows = $_REQUEST['num_rows_sent'];
$start_index = $_REQUEST['index_start_sent'];
$num_max_rows = $_REQUEST['num_max_sent'];
$notice = isset($_REQUEST['notice_sent']) ? $_REQUEST['notice_sent'] : '';

if ((! empty($_REQUEST['index_start_sent']) || $start_index == 0)
    && ! empty($_REQUEST['num_max_sent'])
) {
    $sql_query .= ' LIMIT ' . $start_index . ', ' . $num_max_rows . ' ';
}

$header_html = PMA_getHtmlForPrintViewHeader($db, $sql_query, $num_rows);

$response->addHTML($header_html);

$response->addHTML($notice);

$table_html = '<table>';
$table_html .= '<thead>';
$table_html .= '<tr>';

$columns_heads = json_decode($_REQUEST['columns_sent'], true);
$column_count = count($columns_heads);

foreach ($columns_heads as $value) {
    $table_html .= '<th>';
    $table_html .= $value;
    $table_html .= '</th>';
}

$table_html .= '</tr>';
$table_html .= '</thead>';
$table_html .= '<tbody>';
$table_html .= '<tr>';

$rows = json_decode($_REQUEST['rows_sent'], true);

$odd_row = true;
$count_tables = count($rows);

foreach ($rows as $row) {
    $table_html .= '<tr class="' .  ($odd_row ? 'odd' : 'even') . '">';

    foreach ($row as $value) {
        $table_html .= '<td>';
        if ($value == 'NULL') {
            $table_html .= '<i>';
            $table_html .= $value;
            $table_html .= '</i>';
        } else {
            $table_html .= $value;
        }
        $table_html .= '</td>';
    }
    $odd_row = !($odd_row);
    $table_html .= '</tr>';
}

$table_html .= '</tbody>';
$table_html .= '</table>';

$response->addHTML($table_html);

/**
 * Displays the footer
 */
$response->addHTML(PMA_Util::getButton());