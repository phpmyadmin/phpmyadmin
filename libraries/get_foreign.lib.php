<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/Table.class.php';

/**
 * Gets foreign keys in preparation for a drop-down selector
 * Thanks to <markus@noga.de>
 */


// lem9: we always show the foreign field in the drop-down; if a display
// field is defined, we show it besides the foreign field
$foreign_link = false;
if ($foreigners && isset($foreigners[$field])) {
    $foreigner       = $foreigners[$field];
    $foreign_db      = $foreigner['foreign_db'];
    $foreign_table   = $foreigner['foreign_table'];
    $foreign_field   = $foreigner['foreign_field'];

    // Count number of rows in the foreign table. Currently we do
    // not use a drop-down if more than 200 rows in the foreign table,
    // for speed reasons and because we need a better interface for this.
    //
    // We could also do the SELECT anyway, with a LIMIT, and ensure that
    // the current value of the field is one of the choices.

    $the_total   = PMA_Table::countRecords($foreign_db, $foreign_table, TRUE);

    if ((isset($override_total) && $override_total == true) || $the_total < $cfg['ForeignKeyMaxLimit']) {
        // foreign_display can be FALSE if no display field defined:
        $foreign_display = PMA_getDisplayField($foreign_db, $foreign_table);

        $f_query_main = 'SELECT ' . PMA_backquote($foreign_field)
                        . (($foreign_display == FALSE) ? '' : ', ' . PMA_backquote($foreign_display));
        $f_query_from = ' FROM ' . PMA_backquote($foreign_db) . '.' . PMA_backquote($foreign_table);
        $f_query_filter = empty($foreign_filter) ? '' : ' WHERE ' . PMA_backquote($foreign_field)
                            . ' LIKE "%' . PMA_sqlAddslashes($foreign_filter, TRUE) . '%"'
                            . (($foreign_display == FALSE) ? '' : ' OR ' . PMA_backquote($foreign_display)
                                . ' LIKE "%' . PMA_sqlAddslashes($foreign_filter, TRUE) . '%"'
                                );
        $f_query_order = ($foreign_display == FALSE) ? '' :' ORDER BY ' . PMA_backquote($foreign_table) . '.' . PMA_backquote($foreign_display);
        $f_query_limit = isset($foreign_limit) ? $foreign_limit : '';

        if (!empty($foreign_filter)) {
            $res = PMA_DBI_query('SELECT COUNT(*)' . $f_query_from . $f_query_filter);
            if ($res) {
                $the_total = PMA_DBI_fetch_value($res);
                @PMA_DBI_free_result($res);
            } else {
                $the_total = 0;
            }
        }

        $disp            = PMA_DBI_query($f_query_main . $f_query_from . $f_query_filter . $f_query_order . $f_query_limit);
        if ($disp && PMA_DBI_num_rows($disp) > 0) {
            // garvin: If a resultset has been created, pre-cache it in the $disp_row array
            // This helps us from not needing to use mysql_data_seek by accessing a pre-cached
            // PHP array. Usually those resultsets are not that big, so a performance hit should
            // not be expected.
            $disp_row = array();
            while ($single_disp_row = @PMA_DBI_fetch_assoc($disp)) {
                $disp_row[] = $single_disp_row;
            }
            @PMA_DBI_free_result($disp);
        }
    } else {
        unset($disp_row);
        $foreign_link = true;
    }
}  // end if $foreigners

?>
