<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Does the common work
 */
require_once 'libraries/common.inc.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('export.js');

require 'libraries/server_common.inc.php';

$export_page_title = __('View dump (schema) of databases') . "\n";

$multi_values = '<div style="text-align: left">';
$multi_values .= '<a href="#"';
$multi_values .= ' onclick="setSelectOptions(\'dump\', \'db_select[]\', true); return false;">';
$multi_values .= __('Select All');
$multi_values .= '</a>';
$multi_values .= ' / ';
$multi_values .= '<a href="#"';
$multi_values .= ' onclick="setSelectOptions(\'dump\', \'db_select[]\', false); return false;">';
$multi_values .= __('Unselect All') . '</a><br />';

$multi_values .= '<select name="db_select[]" id="db_select" size="10" multiple="multiple">';
$multi_values .= "\n";

// Check if the selected databases are defined in $_GET (from clicking Back button on export.php)
if (isset($_GET['db_select'])) {
    $_GET['db_select'] = urldecode($_GET['db_select']);
    $_GET['db_select'] = explode(",", $_GET['db_select']);
}

foreach ($GLOBALS['pma']->databases as $current_db) {
    if ($current_db == 'information_schema'
        || $current_db == 'performance_schema'
        || $current_db == 'mysql'
    ) {
        continue;
    }
    if (isset($_GET['db_select'])) {
        if (in_array($current_db, $_GET['db_select'])) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
    } elseif (isset($tmp_select)) {
        if (strpos(' ' . $tmp_select, '|' . $current_db . '|')) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
    } else {
        $is_selected = ' selected="selected"';
    }
    $current_db   = htmlspecialchars($current_db);
    $multi_values .= '                <option value="' . $current_db . '"'
        . $is_selected . '>' . $current_db . '</option>' . "\n";
} // end while
$multi_values .= "\n";
$multi_values .= '</select></div>';

$export_type = 'server';
require_once 'libraries/display_export.lib.php';

?>
