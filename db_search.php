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

/**
 * Gets some core libraries and send headers
 */
require 'libraries/db_common.inc.php';

/**
 * init
 */
// If config variable $GLOBALS['cfg']['Usedbsearch'] is on false : exit.
if (! $GLOBALS['cfg']['UseDbSearch']) {
    PMA_mysqlDie(__('Access denied'), '', false, $err_url);
} // end if
$url_query .= '&amp;goto=db_search.php';
$url_params['goto'] = 'db_search.php';

/**
 * @global array list of tables from the current database
 * but do not clash with $tables coming from db_info.inc.php
 */
$tables_names_only = PMA_DBI_get_tables($GLOBALS['db']);

$search_options = array(
    '1' => __('at least one of the words'),
    '2' => __('all words'),
    '3' => __('the exact phrase'),
    '4' => __('as regular expression'),
);

if (empty($_REQUEST['search_option'])
    || ! is_string($_REQUEST['search_option'])
    || ! array_key_exists($_REQUEST['search_option'], $search_options)
) {
    $search_option = 1;
    unset($_REQUEST['submit_search']);
} else {
    $search_option = (int) $_REQUEST['search_option'];
    $option_str = $search_options[$_REQUEST['search_option']];
}

if (empty($_REQUEST['search_str']) || ! is_string($_REQUEST['search_str'])) {
    unset($_REQUEST['submit_search']);
    $searched = '';
} else {
    $searched = htmlspecialchars($_REQUEST['search_str']);
    // For "as regular expression" (search option 4), we should not treat
    // this as an expression that contains a LIKE (second parameter of
    // PMA_sqlAddSlashes()).
    //
    // Usage example: If user is seaching for a literal $ in a regexp search,
    // he should enter \$ as the value.
    $search_str = PMA_sqlAddSlashes(
        $_REQUEST['search_str'], ($search_option == 4 ? false : true)
    );
}

$tables_selected = array();
if (empty($_REQUEST['table_select']) || ! is_array($_REQUEST['table_select'])) {
    unset($_REQUEST['submit_search']);
} elseif (! isset($_REQUEST['selectall']) && ! isset($_REQUEST['unselectall'])) {
    $tables_selected = array_intersect($_REQUEST['table_select'], $tables_names_only);
}

if (isset($_REQUEST['selectall'])) {
    $tables_selected = $tables_names_only;
} elseif (isset($_REQUEST['unselectall'])) {
    $tables_selected = array();
}

if (empty($_REQUEST['criteriaColumnName'])
    || ! is_string($_REQUEST['criteriaColumnName'])
) {
    unset($criteriaColumnName);
} else {
    $criteriaColumnName = PMA_sqlAddSlashes($_REQUEST['criteriaColumnName'], true);
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
            $tables_selected, $searched, $option_str,
            $search_str, $search_option,
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
        $searched, $search_option, $tables_names_only, $tables_selected, $url_params,
        (! empty($criteriaColumnName) ? $criteriaColumnName : '')
    )
);
?>
