<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * searchs the entire database
 *
 * @todo    make use of UNION when searching multiple tables
 * @todo    display executed query, optional?
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/DbSearch.class.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_search.js');
$scripts->addFile('sql.js');
$scripts->addFile('makegrid.js');
$scripts->addFile('jquery/jquery-ui-timepicker-addon.js');

require 'libraries/db_common.inc.php';

// If config variable $GLOBALS['cfg']['Usedbsearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    PMA_Util::mysqlDie(
        __('Access denied'), '', false, $err_url
    );
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

// Create a database search instance
$db_search = new PMA_DbSearch($GLOBALS['db']);

// Display top links if we are not in an Ajax request
if ( $GLOBALS['is_ajax_request'] != true) {
    include 'libraries/db_info.inc.php';
}
$response->addHTML('<div id="searchresults">');

// Main search form has been submitted, get results
if (isset($_REQUEST['submit_search'])) {
    $response->addHTML($db_search->getSearchResults());
}

// If we are in an Ajax request, we need to exit after displaying all the HTML
if ($GLOBALS['is_ajax_request'] == true && empty($_REQUEST['ajax_page_request'])) {
    exit;
}

// Display the search form
$response->addHTML($db_search->getSelectionForm($url_params));
?>
