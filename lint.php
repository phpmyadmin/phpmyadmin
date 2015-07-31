<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Represents the interface between the linter and  the query editor.
 *
 * @package PhpMyAdmin
 */

/**
 * Loading common files. Used to check for authorization, localization and to
 * load the parsing library.
 */
require_once 'libraries/common.inc.php';

/**
 * Loads the linter.
 */
require_once 'libraries/Linter.class.php';

/**
 * The SQL query to be analyzed.
 *
 * This does not need to be checked again XSS or MySQL injections because it is
 * never executed, just parsed.
 *
 * The client, which will recieve the JSON response will decode the message and
 * and any HTML fragments that are displayed to the user will be encoded anyway.
 *
 * @var string
 */
$sql_query = !empty($_POST['sql_query']) ? $_POST['sql_query'] : '';

// Disabling standard response.
$response = PMA_Response::getInstance();
$response->disable();

echo json_encode(PMA_Linter::lint($sql_query));
