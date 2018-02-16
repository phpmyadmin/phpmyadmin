<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table create form and handles it
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Get some core libraries
 */
require_once 'libraries/common.inc.php';

// Check parameters
Util::checkParameters(array('db'));

/* Check if database name is empty */
if (strlen($db) === 0) {
    Util::mysqlDie(
        __('The database name is empty!'), '', false, 'index.php'
    );
}

/**
 * Selects the database to work with
 */
if (!$GLOBALS['dbi']->selectDb($db)) {
    Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        false,
        'index.php'
    );
}

if ($GLOBALS['dbi']->getColumns($db, $table)) {
    // table exists already
    Util::mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        false,
        'db_structure.php' . Url::getCommon(array('db' => $db))
    );
}

$createAddField = new CreateAddField($GLOBALS['dbi']);

// for libraries/tbl_columns_definition_form.inc.php
// check number of fields to be created
$num_fields = $createAddField->getNumberOfFieldsFromRequest();

$action = 'tbl_create.php';

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {
    $sql_query = $createAddField->getTableCreationQuery($db, $table);

    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {
        Core::previewSQL($sql_query);
    }
    // Executes the query
    $result = $GLOBALS['dbi']->tryQuery($sql_query);

    if ($result) {
        // Update comment table for mime types [MIME]
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && strlen($_REQUEST['field_name'][$fieldindex]) > 0
                ) {
                    Transformations::setMIME(
                        $db, $table,
                        $_REQUEST['field_name'][$fieldindex], $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex],
                        $_REQUEST['field_input_transformation'][$fieldindex],
                        $_REQUEST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }
        }
    } else {
        $response = Response::getInstance();
        $response->setRequestStatus(false);
        $response->addJSON('message', $GLOBALS['dbi']->getError());
    }
    exit;
} // end do create table

//This global variable needs to be reset for the headerclass to function properly
$GLOBAL['table'] = '';

/**
 * Displays the form used to define the structure of the table
 */
require 'libraries/tbl_columns_definition_form.inc.php';
