<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


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

    $the_total   = PMA_countRecords($foreign_db, $foreign_table, TRUE);

    if ((isset($override_total) && $override_total == true) || $the_total < 200) {
        // foreign_display can be FALSE if no display field defined:

        $foreign_display = PMA_getDisplayField($foreign_db, $foreign_table);
        $dispsql         = 'SELECT ' . PMA_backquote($foreign_field)
                         . (($foreign_display == FALSE) ? '' : ', ' . PMA_backquote($foreign_display))
                         . ' FROM ' . PMA_backquote($foreign_db) . '.' . PMA_backquote($foreign_table)
                         . (($foreign_display == FALSE) ? '' :' ORDER BY ' . PMA_backquote($foreign_table) . '.' . PMA_backquote($foreign_display))
                         . (isset($foreign_limit) ? $foreign_limit : '');
        $disp            = PMA_mysql_query($dispsql);
    }
    else {
        unset($disp);
        $foreign_link = true;
    }
} // end if $foreigners

?>
