<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tracking configuration for database
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Util;

/**
 * Run common work
 */
require_once 'libraries/common.inc.php';

//Get some js files needed for Ajax requests
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('db_tracking.js');

/**
 * If we are not in an Ajax request, then do the common work and show the links etc.
 */
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=db_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';
$url_params['back'] = 'db_tracking.php';

// Get the database structure
$sub_part = '_structure';

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

if (isset($_POST['delete_tracking']) && isset($_POST['table'])) {

    Tracker::deleteTracking($GLOBALS['db'], $_POST['table']);
    Message::success(
        __('Tracking data deleted successfully.')
    )->display();

} elseif (isset($_POST['submit_create_version'])) {

    Tracking::createTrackingForMultipleTables($_POST['selected']);
    Message::success(
        sprintf(
            __(
                'Version %1$s was created for selected tables,'
                . ' tracking is active for them.'
            ),
            htmlspecialchars($_POST['version'])
        )
    )->display();

} elseif (isset($_POST['submit_mult'])) {

    if (! empty($_POST['selected_tbl'])) {
        if ($_POST['submit_mult'] == 'delete_tracking') {

            foreach ($_POST['selected_tbl'] as $table) {
                Tracker::deleteTracking($GLOBALS['db'], $table);
            }
            Message::success(
                __('Tracking data deleted successfully.')
            )->display();

        } elseif ($_POST['submit_mult'] == 'track') {

            echo Tracking::getHtmlForDataDefinitionAndManipulationStatements(
                'db_tracking.php' . $url_query,
                0,
                $GLOBALS['db'],
                $_POST['selected_tbl']
            );
            exit;
        }
    } else {
        Message::notice(
            __('No tables selected.')
        )->display();
    }
}

// Get tracked data about the database
$data = Tracker::getTrackedData($GLOBALS['db'], '', '1');

// No tables present and no log exist
if ($num_tables == 0 && count($data['ddlog']) == 0) {
    echo '<p>' , __('No tables found in database.') , '</p>' , "\n";

    if (empty($db_is_system_schema)) {
        echo CreateTable::getHtml($db);
    }
    exit;
}

// ---------------------------------------------------------------------------
$relation = new Relation();
$cfgRelation = $relation->getRelationsParam();

// Prepare statement to get HEAD version
$all_tables_query = ' SELECT table_name, MAX(version) as version FROM ' .
    Util::backquote($cfgRelation['db']) . '.' .
    Util::backquote($cfgRelation['tracking']) .
    ' WHERE db_name = \'' . $GLOBALS['dbi']->escapeString($GLOBALS['db']) .
    '\' ' .
    ' GROUP BY table_name' .
    ' ORDER BY table_name ASC';

$all_tables_result = $relation->queryAsControlUser($all_tables_query);

// If a HEAD version exists
if (is_object($all_tables_result)
    && $GLOBALS['dbi']->numRows($all_tables_result) > 0
) {
    echo Tracking::getHtmlForTrackedTables(
        $GLOBALS['db'], $all_tables_result, $url_query, $pmaThemeImage,
        $text_dir, $cfgRelation
    );
}

$untracked_tables = Tracking::getUntrackedTables($GLOBALS['db']);

// If untracked tables exist
if (count($untracked_tables) > 0) {
    echo Tracking::getHtmlForUntrackedTables(
        $GLOBALS['db'], $untracked_tables, $url_query, $pmaThemeImage, $text_dir
    );
}
// If available print out database log
if (count($data['ddlog']) > 0) {
    $log = '';
    foreach ($data['ddlog'] as $entry) {
        $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
            . $entry['statement'] . "\n";
    }
    echo Util::getMessage(__('Database Log'), $log);
}
