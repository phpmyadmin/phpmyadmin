<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table tracking page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $pmaThemeImage, $text_dir, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

//Get some js files needed for Ajax requests
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('table/tracking.js');

define('TABLE_MAY_BE_ABSENT', true);
require ROOT_PATH . 'libraries/tbl_common.inc.php';

/** @var Tracking $tracking */
$tracking = $containerBuilder->get('tracking');

if (Tracker::isActive()
    && Tracker::isTracked($GLOBALS["db"], $GLOBALS["table"])
    && ! (isset($_POST['toggle_activation'])
    && $_POST['toggle_activation'] == 'deactivate_now')
    && ! (isset($_POST['report_export'])
    && $_POST['export_type'] == 'sqldumpfile')
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
$data               = [];
$entries            = [];
$filter_ts_from     = '';
$filter_ts_to       = '';
$filter_users       = [];
$selection_schema   = false;
$selection_data     = false;
$selection_both     = false;

// Init vars for tracking report
if (isset($_POST['report']) || isset($_POST['report_export'])) {
    $data = Tracker::getTrackedData(
        $GLOBALS['db'],
        $GLOBALS['table'],
        $_POST['version']
    );


    if (! isset($_POST['logtype'])) {
        $_POST['logtype'] = 'schema_and_data';
    }
    if ($_POST['logtype'] == 'schema') {
        $selection_schema = true;
    } elseif ($_POST['logtype'] == 'data') {
        $selection_data   = true;
    } else {
        $selection_both   = true;
    }
    if (! isset($_POST['date_from'])) {
        $_POST['date_from'] = $data['date_from'];
    }
    if (! isset($_POST['date_to'])) {
        $_POST['date_to'] = $data['date_to'];
    }
    if (! isset($_POST['users'])) {
        $_POST['users'] = '*';
    }
    $filter_ts_from = strtotime($_POST['date_from']);
    $filter_ts_to   = strtotime($_POST['date_to']);
    $filter_users   = array_map('trim', explode(',', $_POST['users']));
}

// Prepare export
if (isset($_POST['report_export'])) {
    $entries = $tracking->getEntries($data, $filter_ts_from, $filter_ts_to, $filter_users);
}

// Export as file download
if (isset($_POST['report_export'])
    && $_POST['export_type'] == 'sqldumpfile'
) {
    $tracking->exportAsFileDownload($entries);
}

$html = '<br>';

/**
 * Actions
 */
if (isset($_POST['submit_mult'])) {
    if (! empty($_POST['selected_versions'])) {
        if ($_POST['submit_mult'] == 'delete_version') {
            foreach ($_POST['selected_versions'] as $version) {
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

if (isset($_POST['submit_delete_version'])) {
    $html .= $tracking->deleteTrackingVersion($_POST['version']);
}

// Create tracking version
if (isset($_POST['submit_create_version'])) {
    $html .= $tracking->createTrackingVersion();
}

// Deactivate tracking
if (isset($_POST['toggle_activation'])
    && $_POST['toggle_activation'] == 'deactivate_now'
) {
    $html .= $tracking->changeTracking('deactivate');
}

// Activate tracking
if (isset($_POST['toggle_activation'])
    && $_POST['toggle_activation'] == 'activate_now'
) {
    $html .= $tracking->changeTracking('activate');
}

// Export as SQL execution
if (isset($_POST['report_export']) && $_POST['export_type'] == 'execution') {
    $sql_result = $tracking->exportAsSqlExecution($entries);
    $msg = Message::success(__('SQL statements executed.'));
    $html .= $msg->getDisplay();
}

// Export as SQL dump
if (isset($_POST['report_export']) && $_POST['export_type'] == 'sqldump') {
    $html .= $tracking->exportAsSqlDump($entries);
}

/*
 * Schema snapshot
 */
if (isset($_POST['snapshot'])) {
    $html .= $tracking->getHtmlForSchemaSnapshot($url_query);
}
// end of snapshot report

/*
 *  Tracking report
 */
if (isset($_POST['report'])
    && (isset($_POST['delete_ddlog']) || isset($_POST['delete_dmlog']))
) {
    $html .= $tracking->deleteTrackingReportRows($data);
}

if (isset($_POST['report']) || isset($_POST['report_export'])) {
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
 * Main page
 */
$html .= $tracking->getHtmlForMainPage(
    $url_query,
    $url_params,
    $pmaThemeImage,
    $text_dir
);

$html .= '<br class="clearfloat">';

$response->addHTML($html);
