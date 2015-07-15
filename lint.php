<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Represents the interface between the linter and  the query editor.
 *
 * @package PhpMyAdmin
 */

define('PHPMYADMIN', true);

/**
 * Loads the SQL lexer and parser, which are used to detect errors.
 */
require_once 'libraries/sql-parser/autoload.php';

/**
 * Loads the linter.
 */
require_once 'libraries/Linter.class.php';

PMA_Linter::lint($_REQUEST['sql_query']);
