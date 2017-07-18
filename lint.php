<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Represents the interface between the linter and  the query editor.
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\Linter;
use PhpMyAdmin\Response;

$_GET['ajax_request'] = 'true';

/**
 * Loading common files. Used to check for authorization, localization and to
 * load the parsing library.
 */
require_once 'libraries/common.inc.php';

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
Response::getInstance()->disable();

Core::headerJSON();

if (! empty($_POST['options'])) {
    $options = $_POST['options'];

    if (! empty($options['routine_editor'])) {
        $sql_query = 'CREATE PROCEDURE `a`() ' . $sql_query;
    } elseif (! empty($options['trigger_editor'])) {
        $sql_query = 'CREATE TRIGGER `a` AFTER INSERT ON `b` FOR EACH ROW '
            . $sql_query;
    } elseif (! empty($options['event_editor'])) {
        $sql_query = 'CREATE EVENT `a` ON SCHEDULE EVERY MINUTE DO ' . $sql_query;
    }
}

echo json_encode(Linter::lint($sql_query));
