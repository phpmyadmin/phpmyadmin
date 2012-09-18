<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';

// Check parameters
PMA_Util::checkParameters(array('db', 'table'));

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
            $message = PMA_Message::error(__('Can\'t move table to same one!'));
        } else {
            $message = PMA_Message::error(__('Can\'t copy table to same one!'));
        }
        $result = false;
    } else {
        $result = PMA_Table::moveCopy(
            $db, $table, $_REQUEST['target_db'], $_REQUEST['new_name'],
            $_REQUEST['what'], isset($_REQUEST['submit_move']), 'one_table'
        );

        if (isset($_REQUEST['submit_move'])) {
            $message = PMA_Message::success(__('Table %s has been moved to %s.'));
        } else {
            $message = PMA_Message::success(__('Table %s has been copied to %s.'));
        }
        $old = PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table);
        $message->addParam($old);
        $new = PMA_Util::backquote($_REQUEST['target_db']) . '.'
            . PMA_Util::backquote($_REQUEST['new_name']);
        $message->addParam($new);

        /* Check: Work on new table or on old table? */
        if (isset($_REQUEST['submit_move'])
            || PMA_isValid($_REQUEST['switch_to_new'])
        ) {
            $db    = $_REQUEST['target_db'];
            $table = $_REQUEST['new_name'];
        }
        $reload = 1;
    }
} else {
    /**
     * No new name for the table!
     */
    $message = PMA_Message::error(__('The table name is empty!'));
    $result = false;
}

if ($GLOBALS['is_ajax_request'] == true) {
    $response = PMA_Response::getInstance();
    $response->addJSON('message', $message);
    if ($message->isSuccess()) {
        $response->addJSON('db', $GLOBALS['db']);
        $response->addJSON(
            'sql_query',
            PMA_Util::getMessage(null, $sql_query)
        );
    } else {
        $response->isSuccess(false);
    }
    exit;
}

/**
 * Back to the calling script
 */
$_message = $message;
unset($message);
?>
