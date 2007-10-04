<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Does the common work
 */
require_once './libraries/common.inc.php';

$GLOBALS['js_include'][] = 'functions.js';

/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

$export_page_title = $strViewDumpDatabases . "\n";
$checkall_url = 'server_export.php?'
              . PMA_generate_common_url()
              . '&amp;goto=db_export.php';

$multi_values = '<div align="center">';
$multi_values .= '<a href="' . $checkall_url . '&amp;selectall=1" onclick="setSelectOptions(\'dump\', \'db_select[]\', true); return false;">' . $strSelectAll . '</a>
        /
        <a href="' . $checkall_url . '" onclick="setSelectOptions(\'dump\', \'db_select[]\', false); return false;">' . $strUnselectAll . '</a><br />';

$multi_values .= '<select name="db_select[]" size="6" multiple="multiple">';
$multi_values .= "\n";

foreach ($GLOBALS['PMA_List_Database']->items as $current_db) {
    if (!empty($selectall) || (isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $current_db . '|'))) {
        $is_selected = ' selected="selected"';
    } else {
        $is_selected = '';
    }
    $current_db   = htmlspecialchars($current_db);
    $multi_values .= '                <option value="' . $current_db . '"' . $is_selected . '>' . $current_db . '</option>' . "\n";
} // end while
$multi_values .= "\n";
$multi_values .= '</select></div><br />';

$export_type = 'server';
require_once './libraries/display_export.lib.php';


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
