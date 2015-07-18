<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Represents the interface between the linter and  the query editor.
 *
 * @package PhpMyAdmin
 */

define('PHPMYADMIN', true);

// We load the minimum files required to check if the user is logged in.
require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';
$GLOBALS['PMA_Config'] = new PMA_Config(CONFIG_FILE);
require_once 'libraries/session.inc.php';

// If user is not logged in, he should not send any requests, so we exit here to
// avoid external requests.
if (empty($_SESSION['encryption_key'])) {
    // Unauthorized access detected.
    exit;
}

/**
 * Loads the SQL lexer and parser, which are used to detect errors.
 */
require_once 'libraries/sql-parser/autoload.php';

/**
 * Loads the linter.
 */
require_once 'libraries/Linter.class.php';

// The input of this function does not need to be checked again XSS or MySQL
// injections because it is never executed, just parsed.
// The client, which will recieve the JSON response will decode the message and
// and any HTML fragments that are displayed to the user will be encoded anyway.
PMA_Linter::lint($_REQUEST['sql_query']);
