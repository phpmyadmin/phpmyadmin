<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
$sub_part  = '_export';
require('./db_details_common.php');
$url_query .= '&amp;goto=db_details_export.php';
require('./db_details_db_info.php');

/**
 * Displays the form
 */
?>
<h2>
    <?php echo $strViewDumpDB . "\n"; ?>
</h2>

<?php
$multi_values = '';
if ($num_tables > 1) {

    $multi_values = '<div align="center"><select name="table_select[]" size="6" multiple="multiple">';
    $multi_values .= "\n";

    $i = 0;
    while ($i < $num_tables) {
        $table   = $tables[$i]['Name'];
        if (!empty($selectall) || (isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $table . '|'))) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
        $table   = htmlspecialchars($table);
        $multi_values .= '                <option value="' . $table . '"' . $is_selected . '>' . $table . '</option>' . "\n";
        $i++;
    } // end while
    $multi_values .= "\n";
    $multi_values .= '</select></div>';

    $checkall_url = 'db_details_export.php?'
                  . PMA_generate_common_url($db)
                  . '&amp;goto=db_details_export.php';

    $multi_values .= '<br />
            <a href="' . $checkall_url . '&amp;selectall=1" onclick="setSelectOptions(\'dump\', \'table_select[]\', true); return false;">' . $strSelectAll . '</a>
            &nbsp;/&nbsp;
            <a href="' . $checkall_url . '" onclick="setSelectOptions(\'dump\', \'table_select[]\', false); return false;">' . $strUnselectAll . '</a>
            <br /><br />';
} elseif ($num_tables == 0) {
    echo $strDatabaseNoTable;
    require_once('./footer.inc.php');
} // end if

$export_type = 'database';
require_once('./libraries/display_export.lib.php');

/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
