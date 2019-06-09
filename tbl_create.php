<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table create form and handles it
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config;
use PhpMyAdmin\Core;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

// Check parameters
Util::checkParameters(['db']);

/** @var Transformations $transformations */
$transformations = $containerBuilder->get('transformations');

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

/** @var Config $config */
$config = $containerBuilder->get('config');
$cfg = $config->settings;

/* Check if database name is empty */
if (strlen($db) === 0) {
    Util::mysqlDie(
        __('The database name is empty!'),
        '',
        false,
        'index.php'
    );
}

/**
 * Selects the database to work with
 */
if (! $dbi->selectDb($db)) {
    Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        false,
        'index.php'
    );
}

if ($dbi->getColumns($db, $table)) {
    // table exists already
    Util::mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        false,
        'db_structure.php' . Url::getCommon(['db' => $db])
    );
}

$createAddField = new CreateAddField($dbi);

// for libraries/tbl_columns_definition_form.inc.php
// check number of fields to be created
$num_fields = $createAddField->getNumberOfFieldsFromRequest();

$action = 'tbl_create.php';

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_POST['do_save_data'])) {
    // lower_case_table_names=1 `DB` becomes `db`
    if ($dbi->getLowerCaseNames() === '1') {
        $db = mb_strtolower(
            $db
        );
        $table = mb_strtolower(
            $table
        );
    }
    $sql_query = $createAddField->getTableCreationQuery($db, $table);

    // If there is a request for SQL previewing.
    if (isset($_POST['preview_sql'])) {
        Core::previewSQL($sql_query);
    }
    // Executes the query
    $result = $dbi->tryQuery($sql_query);

    if ($result) {
        // Update comment table for mime types [MIME]
        if (isset($_POST['field_mimetype'])
            && is_array($_POST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            foreach ($_POST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_POST['field_name'][$fieldindex])
                    && strlen($_POST['field_name'][$fieldindex]) > 0
                ) {
                    $transformations->setMime(
                        $db,
                        $table,
                        $_POST['field_name'][$fieldindex],
                        $mimetype,
                        $_POST['field_transformation'][$fieldindex],
                        $_POST['field_transformation_options'][$fieldindex],
                        $_POST['field_input_transformation'][$fieldindex],
                        $_POST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }
        }
    } else {
        $response->setRequestStatus(false);
        $response->addJSON('message', $dbi->getError());
    }
    exit;
} // end do create table

//This global variable needs to be reset for the headerclass to function properly
$GLOBAL['table'] = '';

/**
 * Displays the form used to define the structure of the table
 */
require ROOT_PATH . 'libraries/tbl_columns_definition_form.inc.php';
