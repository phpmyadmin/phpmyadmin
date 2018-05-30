<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table tracking page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Response;

require_once './libraries/common.inc.php';

//Get some js files needed for Ajax requests
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('tbl_tracking.js');

define('TABLE_MAY_BE_ABSENT', true);
require './libraries/tbl_common.inc.php';

$tracking = new Tracking();

if (Tracker::isActive()
    && Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])
    && ! (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'deactivate_now')
    && ! (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile')
) {
    $msg = Message::notice(
        sprintf(
            __('Tracking of %s is activated.'),
            htmlspecialchars($GLOBALS["db"] . '.' . $GLOBALS["table"])
        )
    );
    $response->addHTML($msg->getDisplay());
}

$url_query .= '&amp;goto=tbl_tracking.php&amp;back=tbl_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';
$url_params['back'] = 'tbl_tracking.php';

// Init vars for tracking report
if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $data = Tracker::getTrackedData(
        $_REQUEST['db'],
        $_REQUEST['table'],
        $_REQUEST['version']
    );

    $selection_schema = false;
    $selection_data   = false;
    $selection_both  = false;

    if (! isset($_REQUEST['logtype'])) {
        $_REQUEST['logtype'] = 'schema_and_data';
    }
    if ($_REQUEST['logtype'] == 'schema') {
        $selection_schema = true;
    } elseif ($_REQUEST['logtype'] == 'data') {
        $selection_data   = true;
    } else {
        $selection_both   = true;
    }
    if (! isset($_REQUEST['date_from'])) {
        $_REQUEST['date_from'] = $data['date_from'];
    }
    if (! isset($_REQUEST['date_to'])) {
        $_REQUEST['date_to'] = $data['date_to'];
    }
    if (! isset($_REQUEST['users'])) {
        $_REQUEST['users'] = '*';
    }
    $filter_ts_from = strtotime($_REQUEST['date_from']);
    $filter_ts_to   = strtotime($_REQUEST['date_to']);
    $filter_users   = array_map('trim', explode(',', $_REQUEST['users']));
}

// Prepare export
if (isset($_REQUEST['report_export'])) {
    $entries = $tracking->getEntries($data, $filter_ts_from, $filter_ts_to, $filter_users);
}

// Export as file download
if (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile'
) {
    $tracking->exportAsFileDownload($entries);
}

$html = '<br />';

/**
 * Actions
 */
if (isset($_REQUEST['submit_mult'])) {
    if (! empty($_REQUEST['selected_versions'])) {
        if ($_REQUEST['submit_mult'] == 'delete_version') {
            foreach ($_REQUEST['selected_versions'] as $version) {
                $tracking->deleteTrackingVersion($version);
            }
            $html .= Message::success(
                __('Tracking versions deleted successfully.')
            )->getDisplay();
        }
    } else {
        $html .= Message::notice(
            __('No versions selected.')
        )->getDisplay();
    }
}

if (isset($_REQUEST['submit_delete_version'])) {
    $html .= $tracking->deleteTrackingVersion($_REQUEST['version']);
}

// Create tracking version
if (isset($_REQUEST['submit_create_version'])) {
    $html .= $tracking->createTrackingVersion();
}

// Deactivate tracking
if (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'deactivate_now'
) {
    $html .= $tracking->changeTracking('deactivate');
}

// Activate tracking
if (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'activate_now'
) {
    $html .= $tracking->changeTracking('activate');
}

// Export as SQL execution
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'execution') {
    $sql_result = $tracking->exportAsSqlExecution($entries);
    $msg = Message::success(__('SQL statements executed.'));
    $html .= $msg->getDisplay();
}

// Export as SQL dump
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldump') {
    $html .= $tracking->exportAsSqlDump($entries);
}

/*
 * Schema snapshot
 */
if (isset($_REQUEST['snapshot'])) {
    $html .= $tracking->getHtmlForSchemaSnapshot($url_query);
}
// end of snapshot report

/*
 *  Tracking report
 */
if (isset($_REQUEST['report'])
    && (isset($_REQUEST['delete_ddlog']) || isset($_REQUEST['delete_dmlog']))
) {
    $html .= $tracking->deleteTrackingReportRows($data);
}

if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $html .= $tracking->getHtmlForTrackingReport(
        $url_query,
        $data,
        $url_params,
        $selection_schema,
        $selection_data,
        $selection_both,
        $filter_ts_to,
        $filter_ts_from,
        $filter_users
    );
} // end of report


/*
 * List selectable tables
 */
$selectable_tables_sql_result = $tracking->getSqlResultForSelectableTables();
if ($GLOBALS['dbi']->numRows($selectable_tables_sql_result) > 0) {
    $html .= $tracking->getHtmlForSelectableTables(
        $selectable_tables_sql_result,
        $url_query
    );
}
$html .= '<br />';

/*
 * List versions of current table
 */
$sql_result = $tracking->getListOfVersionsOfTable();
$last_version = $tracking->getTableLastVersionNumber($sql_result);
if ($last_version > 0) {
    $html .= $tracking->getHtmlForTableVersionDetails(
        $sql_result,
        $last_version,
        $url_params,
        $url_query,
        $pmaThemeImage,
        $text_dir
    );
}

$type = $GLOBALS['dbi']->getTable($GLOBALS['db'], $GLOBALS['table'])
    ->isView() ? 'view' : 'table';
$html .= $tracking->getHtmlForDataDefinitionAndManipulationStatements(
    'tbl_tracking.php' . $url_query,
    $last_version,
    $GLOBALS['db'],
    [$GLOBALS['table']],
    $type
);

$html .= '<br class="clearfloat"/>';

$response->addHTML($html);
