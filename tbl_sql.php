<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table SQL executor
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

require 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_sql.php&amp;back=tbl_sql.php';

$err_url   = 'tbl_sql.php' . $err_url;
// After a syntax error, we return to this script
// with the typed query in the textarea.
$goto = 'tbl_sql.php';
$back = 'tbl_sql.php';

// Decides what query to show in SQL box.
$query_to_show = isset($_GET['sql_query']) ? $_GET['sql_query'] : true;

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML(
    SqlQueryForm::getHtml(
        $query_to_show, false,
        isset($_POST['delimiter'])
        ? htmlspecialchars($_POST['delimiter'])
        : ';'
    )
);
