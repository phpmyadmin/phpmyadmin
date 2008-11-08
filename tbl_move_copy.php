<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/Table.class.php';

// Check parameters

PMA_checkParameters(array('db', 'table'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_sql.php?' . PMA_generate_common_url($db, $table);


/**
 * Selects the database to work with
 */
PMA_DBI_select_db($db);

$goto = $cfg['DefaultTabTable'];

/** 
 * $_REQUEST['target_db'] could be empty in case we came from an input field 
 * (when there are many databases, no drop-down)
 */
if (empty($_REQUEST['target_db'])) {
    $_REQUEST['target_db'] = $db;
}

/**
 * A target table name has been sent to this script -> do the work
 */
if (PMA_isValid($_REQUEST['new_name'])) {
    if ($db == $_REQUEST['target_db'] && $table == $_REQUEST['new_name']) {
        if (isset($_REQUEST['submit_move'])) {
            $message = PMA_Message::error('strMoveTableSameNames');
        } else {
            $message = PMA_Message::error('strCopyTableSameNames');
        }
        $goto = './tbl_operations.php';
    } else {
        PMA_Table::moveCopy($db, $table, $_REQUEST['target_db'], $_REQUEST['new_name'],
            $_REQUEST['what'], isset($_REQUEST['submit_move']), 'one_table');

        if (isset($_REQUEST['submit_move'])) {
            $message = PMA_Message::success('strMoveTableOK');
        } else {
            $message = PMA_Message::success('strCopyTableOK');
        }
        $old = PMA_backquote($db) . '.' . PMA_backquote($table);
        $message->addParam($old);
        $new = PMA_backquote($_REQUEST['target_db']) . '.' . PMA_backquote($_REQUEST['new_name']);
        $message->addParam($new);

        /* Check: Work on new table or on old table? */
        if (isset($_REQUEST['submit_move']) || PMA_isValid($_REQUEST['switch_to_new'])) {
            $db        = $_REQUEST['target_db'];
            $table     = $_REQUEST['new_name'];
        }
        $reload = 1;

        $disp_query = $sql_query;
        $disp_message = $message;
        unset($sql_query, $message);

        $goto = $cfg['DefaultTabTable'];
    }
} else {
    /**
     * No new name for the table!
     */
    $message = PMA_Message::error('strTableEmpty');
    $goto = './tbl_operations.php';
}

/**
 * Back to the calling script
 */
require $goto;
?>
