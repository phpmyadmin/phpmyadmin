<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * dumps a database
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\Export as DisplayExport;
use PhpMyAdmin\Export;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $db, $table, $url_query, $containerBuilder;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

PageSettings::showGroup('Export');

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('export.js');

/** @var Export $export */
$export = $containerBuilder->get('export');

// $sub_part is used in Util::getDbInfo() to see if we are coming from
// db_export.php, in which case we don't obey $cfg['MaxTableList']
$sub_part  = '_export';
require_once ROOT_PATH . 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_export.php';

list(
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos
) = Util::getDbInfo($db, $sub_part === null ? '' : $sub_part);

/**
 * Displays the form
 */
$export_page_title = __('View dump (schema) of database');

// exit if no tables in db found
if ($num_tables < 1) {
    $response->addHTML(
        Message::error(__('No tables found in database.'))->getDisplay()
    );
    exit;
} // end if

$multi_values  = '<div class="export_table_list_container">';
if (isset($_POST['structure_or_data_forced'])) {
    $force_val = htmlspecialchars($_POST['structure_or_data_forced']);
} else {
    $force_val = 0;
}
$multi_values .= '<input type="hidden" name="structure_or_data_forced" value="'
    . $force_val . '">';
$multi_values .= '<table class="export_table_select">'
    . '<thead><tr><th></th>'
    . '<th>' . __('Tables') . '</th>'
    . '<th class="export_structure">' . __('Structure') . '</th>'
    . '<th class="export_data">' . __('Data') . '</th>'
    . '</tr><tr>'
    . '<td></td>'
    . '<td class="export_table_name all">' . __('Select all') . '</td>'
    . '<td class="export_structure all">'
    . '<input type="checkbox" id="table_structure_all"></td>'
    . '<td class="export_data all"><input type="checkbox" id="table_data_all">'
    . '</td>'
    . '</tr></thead>'
    . '<tbody>';
$multi_values .= "\n";

// when called by libraries/mult_submits.inc.php
if (! empty($_POST['selected_tbl']) && empty($table_select)) {
    $table_select = $_POST['selected_tbl'];
}

foreach ($tables as $each_table) {
    if (isset($_POST['table_select']) && is_array($_POST['table_select'])) {
        $is_checked = $export->getCheckedClause(
            $each_table['Name'],
            $_POST['table_select']
        );
    } elseif (isset($table_select)) {
        $is_checked = $export->getCheckedClause(
            $each_table['Name'],
            $table_select
        );
    } else {
        $is_checked = ' checked="checked"';
    }
    if (isset($_POST['table_structure']) && is_array($_POST['table_structure'])) {
        $structure_checked = $export->getCheckedClause(
            $each_table['Name'],
            $_POST['table_structure']
        );
    } else {
        $structure_checked = $is_checked;
    }
    if (isset($_POST['table_data']) && is_array($_POST['table_data'])) {
        $data_checked = $export->getCheckedClause(
            $each_table['Name'],
            $_POST['table_data']
        );
    } else {
        $data_checked = $is_checked;
    }
    $table_html   = htmlspecialchars($each_table['Name']);
    $multi_values .= '<tr class="marked">';
    $multi_values .= '<td><input type="checkbox" name="table_select[]"'
        . ' value="' . $table_html . '"' . $is_checked . ' class="checkall"></td>';
    $multi_values .= '<td class="export_table_name">'
        . str_replace(' ', '&nbsp;', $table_html) . '</td>';
    $multi_values .= '<td class="export_structure">'
        . '<input type="checkbox" name="table_structure[]"'
        . ' value="' . $table_html . '"' . $structure_checked . '></td>';
    $multi_values .= '<td class="export_data">'
        . '<input type="checkbox" name="table_data[]"'
        . ' value="' . $table_html . '"' . $data_checked . '></td>';
    $multi_values .= '</tr>';
} // end for

$multi_values .= "\n";
$multi_values .= '</tbody></table></div>';

if (! isset($sql_query)) {
    $sql_query = '';
}
if (! isset($num_tables)) {
    $num_tables = 0;
}
if (! isset($unlim_num_rows)) {
    $unlim_num_rows = 0;
}
if ($multi_values === null) {
    $multi_values = '';
}
$response = Response::getInstance();
$displayExport = new DisplayExport();
$response->addHTML(
    $displayExport->getDisplay(
        'database',
        $db,
        $table,
        $sql_query,
        $num_tables,
        $unlim_num_rows,
        $multi_values
    )
);
