<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tracking configuration for database
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Response;
use PMA\libraries\Tracker;

/**
 * Run common work
 */
require_once 'libraries/common.inc.php';

require_once './libraries/tracking.lib.php';
require_once 'libraries/display_create_table.lib.php';

//Get some js files needed for Ajax requests
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('db_tracking.js');

/**
 * If we are not in an Ajax request, then do the common work and show the links etc.
 */
require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=tbl_tracking.php&amp;back=db_tracking.php';

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
) = PMA\libraries\Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

// Work to do?
//  (here, do not use $_REQUEST['db] as it can be crafted)
if (isset($_REQUEST['delete_tracking']) && isset($_REQUEST['table'])) {

    Tracker::deleteTracking($GLOBALS['db'], $_REQUEST['table']);
    PMA\libraries\Message::success(
        __('Tracking data deleted successfully.')
    )->display();

} elseif (isset($_REQUEST['submit_create_version'])) {

    PMA_createTrackingForMultipleTables($_REQUEST['selected']);
    PMA\libraries\Message::success(
        sprintf(
            __(
                'Version %1$s was created for selected tables,'
                . ' tracking is active for them.'
            ),
            htmlspecialchars($_REQUEST['version'])
        )
    )->display();

} elseif (isset($_REQUEST['submit_mult'])) {

    if (! empty($_REQUEST['selected_tbl'])) {
        if ($_REQUEST['submit_mult'] == 'delete_tracking') {

            foreach ($_REQUEST['selected_tbl'] as $table) {
                Tracker::deleteTracking($GLOBALS['db'], $table);
            }
            PMA\libraries\Message::success(
                __('Tracking data deleted successfully.')
            )->display();

        } elseif ($_REQUEST['submit_mult'] == 'track') {

            echo PMA_getHtmlForDataDefinitionAndManipulationStatements(
                'db_tracking.php' . $url_query,
                0,
                $GLOBALS['db'],
                $_REQUEST['selected_tbl']
            );
            exit;
        }
    } else {
        PMA\libraries\Message::notice(
            __('No tables selected.')
        )->display();
    }
}

// Get tracked data about the database
$data = Tracker::getTrackedData($_REQUEST['db'], '', '1');

// No tables present and no log exist
if ($num_tables == 0 && count($data['ddlog']) == 0) {
    echo '<p>' , __('No tables found in database.') , '</p>' , "\n";

    if (empty($db_is_system_schema)) {
        echo PMA_getHtmlForCreateTable($db);
    }
    exit;
}

// ---------------------------------------------------------------------------
$cfgRelation = PMA_getRelationsParam();

// Prepare statement to get HEAD version
$all_tables_query = ' SELECT table_name, MAX(version) as version FROM ' .
    PMA\libraries\Util::backquote($cfgRelation['db']) . '.' .
    PMA\libraries\Util::backquote($cfgRelation['tracking']) .
    ' WHERE db_name = \'' . $GLOBALS['dbi']->escapeString($_REQUEST['db']) .
    '\' ' .
    ' GROUP BY table_name' .
    ' ORDER BY table_name ASC';

$all_tables_result = PMA_queryAsControlUser($all_tables_query);

// If a HEAD version exists
if (is_object($all_tables_result)
    && $GLOBALS['dbi']->numRows($all_tables_result) > 0
) {
    PMA_displayTrackedTables(
        $GLOBALS['db'], $all_tables_result, $url_query, $pmaThemeImage,
        $text_dir, $cfgRelation
    );
}

$untracked_tables = PMA_getUntrackedTables($GLOBALS['db']);

// If untracked tables exist
if (count($untracked_tables) > 0) {
    PMA_displayUntrackedTables(
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
    echo PMA\libraries\Util::getMessage(__('Database Log'), $log);
}
