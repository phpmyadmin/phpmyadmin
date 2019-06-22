<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays add field form and handles it
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config;
use PhpMyAdmin\CreateAddField;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
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

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('table/structure.js');

// Check parameters
Util::checkParameters(['db', 'table']);

/** @var Transformations $transformations */
$transformations = $containerBuilder->get('transformations');

/** @var string $db */
$db = $containerBuilder->getParameter('db');

/** @var string $table */
$table = $containerBuilder->getParameter('table');

/** @var Config $config */
$config = $containerBuilder->get('config');
$cfg = $config->settings;

/**
 * Defines the url to return to in case of error in a sql statement
 */
$err_url = 'tbl_sql.php' . Url::getCommon(
    [
        'db' => $db,
        'table' => $table,
    ]
);

/**
 * The form used to define the field to add has been submitted
 */
$abort = false;

// check number of fields to be created
if (isset($_POST['submit_num_fields'])) {
    if (isset($_POST['orig_after_field'])) {
        $_POST['after_field'] = $_POST['orig_after_field'];
    }
    if (isset($_POST['orig_field_where'])) {
        $_POST['field_where'] = $_POST['orig_field_where'];
    }
    $num_fields = min(
        intval($_POST['orig_num_fields']) + intval($_POST['added_fields']),
        4096
    );
    $regenerate = true;
} elseif (isset($_POST['num_fields']) && intval($_POST['num_fields']) > 0) {
    $num_fields = min(4096, intval($_POST['num_fields']));
} else {
    $num_fields = 1;
}

if (isset($_POST['do_save_data'])) {
    //avoid an incorrect calling of PMA_updateColumns() via
    //tbl_structure.php below
    unset($_POST['do_save_data']);

    $createAddField = new CreateAddField($dbi);

    list($result, $sql_query) = $createAddField->tryColumnCreationQuery($db, $table, $err_url);

    if ($result === true) {
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

        // Go back to the structure sub-page
        $message = Message::success(
            __('Table %1$s has been altered successfully.')
        );
        $message->addParam($table);
        $response->addJSON(
            'message',
            Util::getMessage($message, $sql_query, 'success')
        );
        exit;
    } else {
        $error_message_html = Util::mysqlDie(
            '',
            '',
            false,
            $err_url,
            false
        );
        $response->addHTML($error_message_html);
        $response->setRequestStatus(false);
        exit;
    }
} // end do alter table

/**
 * Displays the form used to define the new field
 */
if ($abort === false) {
    /**
     * Gets tables information
     */
    include_once ROOT_PATH . 'libraries/tbl_common.inc.php';

    $active_page = 'tbl_structure.php';
    /**
     * Display the form
     */
    $action = 'tbl_addfield.php';
    include_once ROOT_PATH . 'libraries/tbl_columns_definition_form.inc.php';
}
