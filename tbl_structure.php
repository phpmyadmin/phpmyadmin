<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like columns, indexes, size, rows
 * and allows manipulation of indexes and columns
 *
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';

/**
 * Function implementations for this script
 */
require_once 'libraries/structure.lib.php';
require_once 'libraries/index.lib.php';
require_once 'libraries/sql.lib.php';
require_once 'libraries/bookmark.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_structure.js');
$scripts->addFile('indexes.js');

/**
 * Handle column moving
 */
if (isset($_REQUEST['move_columns'])
    && is_array($_REQUEST['move_columns'])
    && $response->isAjax()
) {
    PMA_moveColumns($db, $table);
    exit;
}

/**
 * A click on Change has been made for one column
 */
if (isset($_REQUEST['change_column'])) {
    PMA_displayHtmlForColumnChange($db, $table, null, 'tbl_structure.php');
    exit;
}
/**
 * Modifications have been submitted -> updates the table
 */
if (isset($_REQUEST['do_save_data'])) {
    $regenerate = PMA_updateColumns($db, $table);
    if ($regenerate) {
        // This happens when updating failed
        // @todo: do something appropriate
    } else {
        // continue to show the table's structure
        unset($_REQUEST['selected']);
    }
}

/**
 * handle multiple field commands if required
 *
 * submit_mult_*_x comes from IE if <input type="img" ...> is used
 */
$submit_mult = PMA_getMultipleFieldCommandType();

if (! empty($submit_mult)) {
    if (isset($_REQUEST['selected_fld'])) {
        if ($submit_mult == 'browse') {
            // browsing the table displaying only selected columns
            PMA_displayTableBrowseForSelectedColumns(
                $db, $table, $goto, $pmaThemeImage
            );
        } else {
            // handle multiple field commands
            // handle confirmation of deleting multiple columns
            $action = 'tbl_structure.php';
            include 'libraries/mult_submits.inc.php';
            /**
             * if $submit_mult == 'change', execution will have stopped
             * at this point
             */

            if (empty($message)) {
                $message = PMA_Message::success();
            }
        }
    } else {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', __('No column selected.'));
    }
}

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Runs common work
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'tbl_structure.php';

// Check column names for MySQL reserved words
$reserved_word_column_messages = PMA_getReservedWordColumnNameMessages($db, $table);
$response->addHTML($reserved_word_column_messages);

/**
 * Prepares the table structure display
 */


/**
 * Gets tables informations
 */
require_once 'libraries/tbl_info.inc.php';

require_once 'libraries/Index.class.php';

// 2. Gets table keys and retains them
// @todo should be: $server->db($db)->table($table)->primary()
$primary = PMA_Index::getPrimary($table, $db);

$columns_with_unique_index = PMA_getColumnsWithUniqueIndex($db, $table);

// 3. Get fields
$fields = (array) $GLOBALS['dbi']->getColumns($db, $table, null, true);

// Get more complete field information
// For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
// but later, if the analyser returns more information, it
// could be executed for any MySQL version and replace
// the info given by SHOW FULL COLUMNS FROM.
//
// We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL COLUMNS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

$show_create_table = $GLOBALS['dbi']->fetchValue(
    'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
    . PMA_Util::backquote($table),
    0, 1
);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

/**
 * prepare table infos
 */
// action titles (image or string)
$titles = PMA_getActionTitlesArray();

// hidden action titles (image and string)
$hidden_titles = PMA_getHiddenTitlesArray();

//display table structure
require_once 'libraries/display_structure.inc.php';
?>
