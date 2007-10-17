<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * dumps a database
 *
 * @version $Id$
 * @uses    libraries/db_common.inc.php
 * @uses    libraries/db_info.inc.php
 * @uses    libraries/display_export.lib.php
 * @uses    $tables     from libraries/db_info.inc.php
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';

// $sub_part is also used in db_info.inc.php to see if we are coming from
// db_export.php, in which case we don't obey $cfg['MaxTableList']
$sub_part  = '_export';
require_once './libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_export.php';
require_once './libraries/db_info.inc.php';

/**
 * Displays the form
 */
$export_page_title = $strViewDumpDB;

// exit if no tables in db found
if ($num_tables < 1) {
    PMA_Message::error('strNoTablesFound')->display();
    require './libraries/footer.inc.php';
    exit;
} // end if

$checkall_url = 'db_export.php?'
              . PMA_generate_common_url($db)
              . '&amp;goto=db_export.php';

$multi_values = '<div align="center">';
$multi_values .= '<a href="' . $checkall_url . '" onclick="setSelectOptions(\'dump\', \'table_select[]\', true); return false;">' . $strSelectAll . '</a>
        /
        <a href="' . $checkall_url . '&amp;unselectall=1" onclick="setSelectOptions(\'dump\', \'table_select[]\', false); return false;">' . $strUnselectAll . '</a><br />';

$multi_values .= '<select name="table_select[]" size="6" multiple="multiple">';
$multi_values .= "\n";

foreach ($tables as $each_table) {
    // ok we show also views
    //if (is_null($each_table['Engine'])) {
        // Don't offer to export views yet.
    //    continue;
    //}
    if (! empty($unselectall)
      || (isset($tmp_select)
           && false !== strpos($tmp_select, '|' . $each_table['Name'] . '|'))) {
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
$multi_values .= '</select></div><br />';

$export_type = 'database';
require_once './libraries/display_export.lib.php';

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>
