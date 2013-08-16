<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table create form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Get some core libraries
 */
require_once 'libraries/common.inc.php';

// for libraries/tbl_columns_definition_form.inc.php
$action = 'tbl_create.php';

// Check parameters
PMA_Util::checkParameters(array('db'));

/* Check if database name is empty */
if (strlen($db) == 0) {
    PMA_Util::mysqlDie(
        __('The database name is empty!'), '', '', 'index.php'
    );
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
if ($GLOBALS['dbi']->getColumns($db, $table)) {
    // table exists already
    PMA_Util::mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        '',
        'db_structure.php?' . PMA_URL_getCommon($db)
    );
}

$err_url = 'tbl_create.php?' . PMA_URL_getCommon($db, $table);

// check number of fields to be created
if (isset($_REQUEST['submit_num_fields'])) {
    $regenerate = true; // for libraries/tbl_columns_definition_form.inc.php
    $num_fields = $_REQUEST['orig_num_fields'] + $_REQUEST['added_fields'];
} elseif (isset($_REQUEST['num_fields']) && intval($_REQUEST['num_fields']) > 0) {
    $num_fields = (int) $_REQUEST['num_fields'];
} else {
    $num_fields = 4;
}

/**
 * Selects the database to work with
 */
if (!$GLOBALS['dbi']->selectDb($db)) {
    PMA_Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        '',
        'index.php'
    );
}

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {
    $sql_query = '';

    include_once 'libraries/create_addfield.lib.php';
    // get column addition statements
    $sql_statement = PMA_getColumnCreationStatements(true);

    // Builds the 'create table' statement
    $sql_query = 'CREATE TABLE ' . PMA_Util::backquote($db) . '.'
        . PMA_Util::backquote($table) . ' (' . $sql_statement . ')';

    // Adds table type, character set, comments and partition definition
    if (!empty($_REQUEST['tbl_storage_engine'])
        && ($_REQUEST['tbl_storage_engine'] != 'Default')
    ) {
        $sql_query .= ' ENGINE = ' . $_REQUEST['tbl_storage_engine'];
    }
    if (!empty($_REQUEST['tbl_collation'])) {
        $sql_query .= PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }
    if (!empty($_REQUEST['comment'])) {
        $sql_query .= ' COMMENT = \''
            . PMA_Util::sqlAddSlashes($_REQUEST['comment']) . '\'';
    }
    if (!empty($_REQUEST['partition_definition'])) {
        $sql_query .= ' ' . PMA_Util::sqlAddSlashes(
            $_REQUEST['partition_definition']
        );
    }
    $sql_query .= ';';

    // Executes the query
    $result = $GLOBALS['dbi']->tryQuery($sql_query);

    if (!$result) {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $GLOBALS['dbi']->getError());
    }
    exit;
} // end do create table

/**
 * Displays the form used to define the structure of the table
 */
require 'libraries/tbl_columns_definition_form.inc.php';

?>
