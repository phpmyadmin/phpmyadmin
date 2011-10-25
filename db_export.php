<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * dumps a database
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'export.js';

// $sub_part is also used in db_info.inc.php to see if we are coming from
// db_export.php, in which case we don't obey $cfg['MaxTableList']
$sub_part  = '_export';
require_once './libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_export.php';
require_once './libraries/db_info.inc.php';

/**
 * Displays the form
 */
$export_page_title = __('View dump (schema) of database');

// exit if no tables in db found
if ($num_tables < 1) {
    PMA_Message::error(__('No tables found in database.'))->display();
    include './libraries/footer.inc.php';
    exit;
} // end if

$checkall_url = 'db_export.php?'
              . PMA_generate_common_url($db)
              . '&amp;goto=db_export.php';

$multi_values = '<div>';
$multi_values .= '<a href="' . $checkall_url . '" onclick="setSelectOptions(\'dump\', \'table_select[]\', true); return false;">' . __('Select All') . '</a>
        /
        <a href="' . $checkall_url . '&amp;unselectall=1" onclick="setSelectOptions(\'dump\', \'table_select[]\', false); return false;">' . __('Unselect All') . '</a><br />';

$multi_values .= '<select name="table_select[]" id="table_select" size="10" multiple="multiple">';
$multi_values .= "\n";

if (!empty($selected_tbl) && empty($table_select)) {
    $table_select = $selected_tbl;
}

// Check if the selected tables are defined in $_GET (from clicking Back button on export.php)
if (isset($_GET['table_select'])) {
    $_GET['table_select'] = urldecode($_GET['table_select']);
    $_GET['table_select'] = explode(",", $_GET['table_select']);
}

foreach ($tables as $each_table) {
    if (isset($_GET['table_select'])) {
        if (in_array($each_table['Name'], $_GET['table_select'])) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
    } elseif (! empty($unselectall)
            || (! empty($table_select) && !in_array($each_table['Name'], $table_select))) {
        $is_selected = '';
    } else {
        $is_selected = ' selected="selected"';
    }
    $table_html   = htmlspecialchars($each_table['Name']);
    $multi_values .= '                <option value="' . $table_html . '"'
        . $is_selected . '>'
        . str_replace(' ', '&nbsp;', $table_html) . '</option>' . "\n";
} // end for

$multi_values .= "\n";
$multi_values .= '</select></div>';

$export_type = 'database';
require_once './libraries/display_export.lib.php';

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
