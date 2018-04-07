<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * searches the entire database
 *
 * @todo    make use of UNION when searching multiple tables
 * @todo    display executed query, optional?
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Database\Search;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

/**
* Gets some core libraries
*/
require_once 'libraries/common.inc.php';

$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_search.js');
$scripts->addFile('sql.js');
$scripts->addFile('makegrid.js');

require 'libraries/db_common.inc.php';

// If config variable $GLOBALS['cfg']['UseDbSearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    Util::mysqlDie(
        __('Access denied!'), '', false, $err_url
    );
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

// Create a database search instance
$db_search = new Search($GLOBALS['db']);

// Display top links if we are not in an Ajax request
if (! $response->isAjax()) {
    list(
        $tables,
        $num_tables,
        $total_num_tables,
        $sub_part,
        $is_show_stats,
        $db_is_system_schema,
        $tooltip_truename,
        $tooltip_aliasname,
        $pos
    ) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');
}

// Main search form has been submitted, get results
if (isset($_REQUEST['submit_search'])) {
    $response->addHTML($db_search->getSearchResults());
}

// If we are in an Ajax request, we need to exit after displaying all the HTML
if ($response->isAjax() && empty($_REQUEST['ajax_page_request'])) {
    exit;
}

// Display the search form
$response->addHTML($db_search->getSelectionForm());
$response->addHTML('<div id="searchresults"></div>');
$response->addHTML(
    '<div id="togglesearchresultsdiv"><a id="togglesearchresultlink"></a></div>'
);
$response->addHTML('<br class="clearfloat" />');
$response->addHTML($db_search->getResultDivs());
