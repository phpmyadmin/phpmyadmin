<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * SQL executor
 *
 * @todo    we must handle the case if sql.php is called directly with a query
 *          that returns 0 rows - to prevent cyclic redirects or includes
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ParseAnalyze;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $cfg, $containerBuilder, $pmaThemeImage;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

/** @var CheckUserPrivileges $checkUserPrivileges */
$checkUserPrivileges = $containerBuilder->get('check_user_privileges');
$checkUserPrivileges->getPrivileges();

PageSettings::showGroup('Browse');

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
$scripts->addFile('table/change.js');
$scripts->addFile('indexes.js');
$scripts->addFile('gis_data_editor.js');
$scripts->addFile('multi_column_sort.js');

/** @var Sql $sql */
$sql = $containerBuilder->get('sql');

/**
 * Set ajax_reload in the response if it was already set
 */
if (isset($ajax_reload) && $ajax_reload['reload'] === true) {
    $response->addJSON('ajax_reload', $ajax_reload);
}

/**
 * Defines the url to return to in case of error in a sql statement
 */
$is_gotofile  = true;
if (empty($goto)) {
    if (empty($table)) {
        $goto = Util::getScriptNameForOption(
            $cfg['DefaultTabDatabase'],
            'database'
        );
    } else {
        $goto = Util::getScriptNameForOption(
            $cfg['DefaultTabTable'],
            'table'
        );
    }
} // end if

if (! isset($err_url)) {
    $err_url = (! empty($back) ? $back : $goto)
        . '?' . Url::getCommon(['db' => $GLOBALS['db']])
        . ((mb_strpos(' ' . $goto, 'db_') != 1
            && strlen($table) > 0)
            ? '&amp;table=' . urlencode($table)
            : ''
        );
} // end if

// Coming from a bookmark dialog
if (isset($_POST['bkm_fields']['bkm_sql_query'])) {
    $sql_query = $_POST['bkm_fields']['bkm_sql_query'];
} elseif (isset($_POST['sql_query'])) {
    $sql_query = $_POST['sql_query'];
} elseif (isset($_GET['sql_query']) && isset($_GET['sql_signature'])) {
    if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
        $sql_query = $_GET['sql_query'];
    }
}

// This one is just to fill $db
if (isset($_POST['bkm_fields']['bkm_database'])) {
    $db = $_POST['bkm_fields']['bkm_database'];
}

// During grid edit, if we have a relational field, show the dropdown for it.
if (isset($_POST['get_relational_values'])
    && $_POST['get_relational_values'] == true
) {
    $sql->getRelationalValues($db, $table);
    // script has exited at this point
}

// Just like above, find possible values for enum fields during grid edit.
if (isset($_POST['get_enum_values']) && $_POST['get_enum_values'] == true) {
    $sql->getEnumOrSetValues($db, $table, "enum");
    // script has exited at this point
}


// Find possible values for set fields during grid edit.
if (isset($_POST['get_set_values']) && $_POST['get_set_values'] == true) {
    $sql->getEnumOrSetValues($db, $table, "set");
    // script has exited at this point
}

if (isset($_GET['get_default_fk_check_value'])
    && $_GET['get_default_fk_check_value'] == true
) {
    $response = Response::getInstance();
    $response->addJSON(
        'default_fk_check_value',
        Util::isForeignKeyCheck()
    );
    exit;
}

/**
 * Check ajax request to set the column order and visibility
 */
if (isset($_POST['set_col_prefs']) && $_POST['set_col_prefs'] == true) {
    $sql->setColumnOrderOrVisibility($table, $db);
    // script has exited at this point
}

// Default to browse if no query set and we have table
// (needed for browsing from DefaultTabTable)
if (empty($sql_query) && strlen($table) > 0 && strlen($db) > 0) {
    $sql_query = $sql->getDefaultSqlQueryForBrowse($db, $table);

    // set $goto to what will be displayed if query returns 0 rows
    $goto = '';
} else {
    // Now we can check the parameters
    Util::checkParameters(['sql_query']);
}

/**
 * Parse and analyze the query
 */
list(
    $analyzed_sql_results,
    $db,
    $table_from_sql
) = ParseAnalyze::sqlQuery($sql_query, $db);
// @todo: possibly refactor
extract($analyzed_sql_results);

if ($table != $table_from_sql && ! empty($table_from_sql)) {
    $table = $table_from_sql;
}


/**
 * Check rights in case of DROP DATABASE
 *
 * This test may be bypassed if $is_js_confirmed = 1 (already checked with js)
 * but since a malicious user may pass this variable by url/form, we don't take
 * into account this case.
 */
if ($sql->hasNoRightsToDropDatabase(
    $analyzed_sql_results,
    $cfg['AllowUserDropDatabase'],
    $dbi->isSuperuser()
)) {
    Util::mysqlDie(
        __('"DROP DATABASE" statements are disabled.'),
        '',
        false,
        $err_url
    );
} // end if

/**
 * Need to find the real end of rows?
 */
if (isset($find_real_end) && $find_real_end) {
    $unlim_num_rows = $sql->findRealEndOfRows($db, $table);
}


/**
 * Bookmark add
 */
if (isset($_POST['store_bkm'])) {
    $sql->addBookmark($goto);
    // script has exited at this point
} // end if


/**
 * Sets or modifies the $goto variable if required
 */
if ($goto == 'sql.php') {
    $is_gotofile = false;
    $goto = 'sql.php' . Url::getCommon(
        [
            'db' => $db,
            'table' => $table,
            'sql_query' => $sql_query,
        ]
    );
} // end if

$sql->executeQueryAndSendQueryResponse(
    $analyzed_sql_results, // analyzed_sql_results
    $is_gotofile, // is_gotofile
    $db, // db
    $table, // table
    isset($find_real_end) ? $find_real_end : null, // find_real_end
    isset($import_text) ? $import_text : null, // sql_query_for_bookmark
    isset($extra_data) ? $extra_data : null, // extra_data
    isset($message_to_show) ? $message_to_show : null, // message_to_show
    isset($message) ? $message : null, // message
    isset($sql_data) ? $sql_data : null, // sql_data
    $goto, // goto
    $pmaThemeImage, // pmaThemeImage
    isset($disp_query) ? $display_query : null, // disp_query
    isset($disp_message) ? $disp_message : null, // disp_message
    isset($query_type) ? $query_type : null, // query_type
    $sql_query, // sql_query
    isset($selected) ? $selected : null, // selectedTables
    isset($complete_query) ? $complete_query : null // complete_query
);
