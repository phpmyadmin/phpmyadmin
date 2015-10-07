<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server SQL executor
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\PageSettings;
use PMA\libraries\Response;

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/config/user_preferences.forms.php';
require_once 'libraries/config/page_settings.forms.php';

PageSettings::showGroup('Sql_queries');

/**
 * Does the common work
 */
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('makegrid.js');
$scripts->addFile('jquery/jquery.uitablefilter.js');
$scripts->addFile('sql.js');

require_once 'libraries/server_common.inc.php';
require_once 'libraries/sql_query_form.lib.php';

/**
 * Query box, bookmark, insert data from textfile
 */
$response->addHTML(PMA_getHtmlForSqlQueryForm());
