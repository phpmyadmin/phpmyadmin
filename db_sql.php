<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Database SQL executor
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;

/**
 *
 */
require_once 'libraries/common.inc.php';

PageSettings::showGroup('Sql');

/**
 * Runs common work
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

require 'libraries/db_common.inc.php';

// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'db_sql.php';
$back = 'db_sql.php';

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML(
    SqlQueryForm::getHtml(
        true, false,
        isset($_POST['delimiter'])
        ? htmlspecialchars($_POST['delimiter'])
        : ';'
    )
);
