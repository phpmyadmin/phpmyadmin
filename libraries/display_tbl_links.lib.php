<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * 
 * - bugfix for select all checkboxes
 * - copy right to left (or left to right) if user click on a check box
 * - reversed the right modify links: 1. drop, 2. edit, 3. checkbox
 * - also changes made in js/functions.js
 *
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if ($doWriteModifyAt == 'left') {

    if (!empty($del_url) && $is_display['del_lnk'] != 'kp') {
        echo '    <td align="center">' . "\n"
           . '        <input type="checkbox" id="id_rows_to_delete' . $row_no . '" name="rows_to_delete[' . $where_clause_html . ']"'
           . ' onclick="copyCheckboxesRange(\'rowsDeleteForm\', \'id_rows_to_delete' . $row_no . '\',\'l\');"'
           . ' value="' . htmlspecialchars($del_query) . '" ' . (isset($GLOBALS['checkall']) ? 'checked="checked"' : '') . ' />' . "\n"
           . '    </td>' . "\n";
    }
    if (!empty($edit_url)) {
        echo '    <td class="' . $edit_anchor_class . '" align="center">' . "\n"
           . PMA_linkOrButton($edit_url, $edit_str, '', FALSE)
           . $bookmark_go
           . '    </td>' . "\n";
    }
    if (!empty($del_url)) {
        echo '    <td align="center">' . "\n"
           . PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''), FALSE)
           . '    </td>' . "\n";
    }
} elseif ($doWriteModifyAt == 'right') {
    if (!empty($del_url)) {
        echo '    <td align="center">' . "\n"
           . PMA_linkOrButton($del_url, $del_str, (isset($js_conf) ? $js_conf : ''), FALSE)
           . '    </td>' . "\n";
    }
    if (!empty($edit_url)) {
        echo '    <td class="' . $edit_anchor_class . '" align="center">' . "\n"
           . PMA_linkOrButton($edit_url, $edit_str, '', FALSE)
           . $bookmark_go
           . '    </td>' . "\n";
    }
    if (!empty($del_url) && $is_display['del_lnk'] != 'kp') {
        echo '    <td align="center">' . "\n"
           . '        <input type="checkbox" id="id_rows_to_delete' . $row_no . 'r" name="rows_to_delete[' . $where_clause_html . ']"'
           . ' onclick="copyCheckboxesRange(\'rowsDeleteForm\', \'id_rows_to_delete' . $row_no . '\',\'r\');"'
           . ' value="' . htmlspecialchars($del_query) . '" ' . (isset($GLOBALS['checkall']) ? 'checked="checked"' : '') . ' />' . "\n"
           . '    </td>' . "\n";
    }
}
/*
 * Where clause for selecting this row uniquely is provided as a hidden input.
 * Used by jQuery scripts for handling inline editing
 */
if( !empty($where_clause)) {
    echo '<input type="hidden" class="where_clause" value ="' . $where_clause_html . '" />';
}
?>
