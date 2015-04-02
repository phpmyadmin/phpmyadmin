<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table tracking page
 *
 * @package PhpMyAdmin
 */

// Run common work
require_once './libraries/common.inc.php';

require_once './libraries/tracking.lib.php';

//Get some js files needed for Ajax requests
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('jquery/jquery.tablesorter.js');
$scripts->addFile('tbl_tracking.js');

define('TABLE_MAY_BE_ABSENT', true);
require './libraries/tbl_common.inc.php';

if (PMA_Tracker::isActive()
    && PMA_Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])
    && ! (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'deactivate_now')
    && ! (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile')
) {
    $msg = PMA_Message::notice(
        sprintf(
            __('Tracking of %s is activated.'),
            htmlspecialchars($GLOBALS["db"] . '.' . $GLOBALS["table"])
        )
    );
    PMA_Response::getInstance()->addHTML($msg->getDisplay());
}

$url_query .= '&amp;goto=tbl_tracking.php&amp;back=tbl_tracking.php';
$url_params['goto'] = 'tbl_tracking.php';
$url_params['back'] = 'tbl_tracking.php';

// Init vars for tracking report
if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $data = PMA_Tracker::getTrackedData(
        $_REQUEST['db'], $_REQUEST['table'], $_REQUEST['version']
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
    $entries = PMA_getEntries($data, $filter_ts_from, $filter_ts_to, $filter_users);
}

// Export as file download
if (isset($_REQUEST['report_export'])
    && $_REQUEST['export_type'] == 'sqldumpfile'
) {
    PMA_exportAsFileDownload($entries);
}

$html = '<br />';

/**
 * Actions
 */
if (isset($_REQUEST['submit_mult'])) {
    if (! empty($_REQUEST['selected_versions'])) {
        if ($_REQUEST['submit_mult'] == 'delete_version') {
            foreach ($_REQUEST['selected_versions'] as $version) {
                PMA_deleteTrackingVersion($version);
            }
            $html .= PMA_Message::success(
                __('Tracking versions deleted successfully.')
            )->getDisplay();
        }
    } else {
        $html .= PMA_Message::notice(
            __('No versions selected.')
        )->getDisplay();
    }
}

if (isset($_REQUEST['submit_delete_version'])) {
    $html .= PMA_deleteTrackingVersion($_REQUEST['version']);
}

// Create tracking version
if (isset($_REQUEST['submit_create_version'])) {
    $html .= PMA_createTrackingVersion();
}

// Deactivate tracking
if (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'deactivate_now'
) {
    $html .= PMA_deactivateTracking();
}

// Activate tracking
if (isset($_REQUEST['toggle_activation'])
    && $_REQUEST['toggle_activation'] == 'activate_now'
) {
    $html .= PMA_activateTracking();
}

// Export as SQL execution
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'execution') {
    $sql_result = PMA_exportAsSQLExecution($entries);
    $msg = PMA_Message::success(__('SQL statements executed.'));
    $html .= $msg->getDisplay();
}

// Export as SQL dump
if (isset($_REQUEST['report_export']) && $_REQUEST['export_type'] == 'sqldump') {
    $html .= PMA_exportAsSQLDump($entries);
}

/*
 * Schema snapshot
 */
if (isset($_REQUEST['snapshot'])) {
    $html .= PMA_getHtmlForSchemaSnapshot($url_query);
}
// end of snapshot report

/*
 *  Tracking report
 */
if (isset($_REQUEST['report'])
    && (isset($_REQUEST['delete_ddlog']) || isset($_REQUEST['delete_dmlog']))
) {
    $html .= PMA_deleteTrackingReportRows($data);
}

if (isset($_REQUEST['report']) || isset($_REQUEST['report_export'])) {
    $html .= PMA_getHtmlForTrackingReport(
        $url_query, $data, $url_params, $selection_schema, $selection_data,
        $selection_both, $filter_ts_to, $filter_ts_from, $filter_users
    );
} // end of report


/*
 * List selectable tables
 */
$selectable_tables_sql_result = PMA_getSQLResultForSelectableTables();
if ($GLOBALS['dbi']->numRows($selectable_tables_sql_result) > 0) {
    $html .= PMA_getHtmlForSelectableTables(
        $selectable_tables_sql_result, $url_query
    );
}
$html .= '<br />';

/*
 * List versions of current table
 */
$sql_result = PMA_getListOfVersionsOfTable();
$last_version = PMA_getTableLastVersionNumber($sql_result);
if ($last_version > 0) {
    $html .= PMA_getHtmlForTableVersionDetails(
        $sql_result, $last_version, $url_params,
        $url_query, $pmaThemeImage, $text_dir
    );
}

$type = PMA_Table::isView($GLOBALS['db'], $GLOBALS['table']) ? 'view' : 'table';
$html .= PMA_getHtmlForDataDefinitionAndManipulationStatements(
    'tbl_tracking.php' . $url_query,
    $last_version,
    $GLOBALS['db'],
    array($GLOBALS['table']),
    $type
);

$html .= '<br class="clearfloat"/>';

$response = PMA_Response::getInstance();
$response->addHTML($html);

?>
