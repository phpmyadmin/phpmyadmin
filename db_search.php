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
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/db_search.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('db_search.js');
$scripts->addFile('sql.js');
$scripts->addFile('makegrid.js');
$scripts->addFile('jquery/timepicker.js');
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * Gets some core libraries and send headers
 */
require 'libraries/db_common.inc.php';

/**
 * init
 */
// If config variable $GLOBALS['cfg']['Usedbsearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    $common_functions->mysqlDie(__('Access denied'), '', false, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

/**
 * @global array list of tables from the current database
 * but do not clash with $tables coming from db_info.inc.php
 */
$tables_names_only = PMA_DBI_get_tables($GLOBALS['db']);

$searchTypes = array(
    '1' => __('at least one of the words'),
    '2' => __('all words'),
    '3' => __('the exact phrase'),
    '4' => __('as regular expression'),
);

if (empty($_REQUEST['criteriaSearchType'])
    || ! is_string($_REQUEST['criteriaSearchType'])
    || ! array_key_exists($_REQUEST['criteriaSearchType'], $searchTypes)
) {
    $criteriaSearchType = 1;
    unset($_REQUEST['submit_search']);
} else {
    $criteriaSearchType = (int) $_REQUEST['criteriaSearchType'];
    $option_str = $searchTypes[$_REQUEST['criteriaSearchType']];
}

if (empty($_REQUEST['criteriaSearchString'])
    || ! is_string($_REQUEST['criteriaSearchString'])
) {
    unset($_REQUEST['submit_search']);
    $searched = '';
} else {
    $searched = htmlspecialchars($_REQUEST['criteriaSearchString']);
    // For "as regular expression" (search option 4), we should not treat
    // this as an expression that contains a LIKE (second parameter of
    // sqlAddSlashes()).
    //
    // Usage example: If user is seaching for a literal $ in a regexp search,
    // he should enter \$ as the value.
    $criteriaSearchString = $common_functions->sqlAddSlashes(
        $_REQUEST['criteriaSearchString'], ($criteriaSearchType == 4 ? false : true)
    );
}

$criteriaTables = array();
if (empty($_REQUEST['criteriaTables']) || ! is_array($_REQUEST['criteriaTables'])) {
    unset($_REQUEST['submit_search']);
} elseif (! isset($_REQUEST['selectall']) && ! isset($_REQUEST['unselectall'])) {
    $criteriaTables = array_intersect(
        $_REQUEST['criteriaTables'], $tables_names_only
    );
}

if (isset($_REQUEST['selectall'])) {
    $criteriaTables = $tables_names_only;
} elseif (isset($_REQUEST['unselectall'])) {
    $criteriaTables = array();
}

if (empty($_REQUEST['criteriaColumnName'])
    || ! is_string($_REQUEST['criteriaColumnName'])
) {
    unset($criteriaColumnName);
} else {
    $criteriaColumnName = $common_functions->sqlAddSlashes(
        $_REQUEST['criteriaColumnName'], true
    );
}

/**
 * Displays top links if we are not in an Ajax request
 */
$sub_part = '';

if ( $GLOBALS['is_ajax_request'] != true) {
    include 'libraries/db_info.inc.php';
    $response->addHTML('<div id="searchresults">');
}

/**
 * Main search form has been submitted
 */
if (isset($_REQUEST['submit_search'])) {
    $response->addHTML(
        PMA_dbSearchGetSearchResults(
            $criteriaTables, $searched, $option_str,
            $criteriaSearchString, $criteriaSearchType,
            (! empty($criteriaColumnName) ? $criteriaColumnName : '')
        )
    );
}

/**
 * If we are in an Ajax request, we need to exit after displaying all the HTML
 */
if ($GLOBALS['is_ajax_request'] == true) {
    exit;
} else {
    $response->addHTML('</div>');//end searchresults div
}

// Add search form
$response->addHTML(
    PMA_dbSearchGetSelectionForm(
        $searched, $criteriaSearchType, $tables_names_only, $criteriaTables,
        $url_params, (! empty($criteriaColumnName) ? $criteriaColumnName : '')
    )
);
?>
