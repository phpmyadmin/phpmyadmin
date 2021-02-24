<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use function array_map;
use function define;
use function explode;
use function htmlspecialchars;
use function sprintf;
use function strtotime;

final class TrackingController extends AbstractController
{
    /** @var Tracking */
    private $tracking;

    /**
     * @param Response $response
     * @param string   $db       Database name.
     * @param string   $table    Table name.
     */
    public function __construct(
        $response,
        Template $template,
        $db,
        $table,
        Tracking $tracking
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->tracking = $tracking;
    }

    public function index(): void
    {
        global $text_dir, $url_params, $msg, $PMA_Theme, $err_url;
        global $data, $entries, $filter_ts_from, $filter_ts_to, $filter_users, $selection_schema;
        global $selection_data, $selection_both, $sql_result, $db, $table, $cfg;

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'table/tracking.js']);

        define('TABLE_MAY_BE_ABSENT', true);

        Util::checkParameters(['db', 'table']);

        $url_params = ['db' => $db, 'table' => $table];
        $err_url = Util::getScriptNameForOption($cfg['DefaultTabTable'], 'table');
        $err_url .= Url::getCommon($url_params, '&');

        DbTableExists::check();

        $activeMessage = '';
        if (Tracker::isActive()
            && Tracker::isTracked($GLOBALS['db'], $GLOBALS['table'])
            && ! (isset($_POST['toggle_activation'])
                && $_POST['toggle_activation'] === 'deactivate_now')
            && ! (isset($_POST['report_export'])
                && $_POST['export_type'] === 'sqldumpfile')
        ) {
            $msg = Message::notice(
                sprintf(
                    __('Tracking of %s is activated.'),
                    htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
                )
            );
            $activeMessage = $msg->getDisplay();
        }

        $url_params['goto'] = Url::getFromRoute('/table/tracking');
        $url_params['back'] = Url::getFromRoute('/table/tracking');

        $data = [];
        $entries = [];
        $filter_ts_from = null;
        $filter_ts_to = null;
        $filter_users = [];
        $selection_schema = false;
        $selection_data = false;
        $selection_both = false;

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
            if ($_POST['logtype'] === 'schema') {
                $selection_schema = true;
            } elseif ($_POST['logtype'] === 'data') {
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
            $filter_ts_to = strtotime($_POST['date_to']);
            $filter_users = array_map('trim', explode(',', $_POST['users']));
        }

        // Prepare export
        if (isset($_POST['report_export'])) {
            $entries = $this->tracking->getEntries(
                $data,
                (int) $filter_ts_from,
                (int) $filter_ts_to,
                $filter_users
            );
        }

        // Export as file download
        if (isset($_POST['report_export'])
            && $_POST['export_type'] === 'sqldumpfile'
        ) {
            $this->tracking->exportAsFileDownload($entries);
        }

        $actionMessage = '';
        if (isset($_POST['submit_mult'])) {
            if (! empty($_POST['selected_versions'])) {
                if ($_POST['submit_mult'] === 'delete_version') {
                    foreach ($_POST['selected_versions'] as $version) {
                        $this->tracking->deleteTrackingVersion($version);
                    }
                    $actionMessage = Message::success(
                        __('Tracking versions deleted successfully.')
                    )->getDisplay();
                }
            } else {
                $actionMessage = Message::notice(
                    __('No versions selected.')
                )->getDisplay();
            }
        }

        $deleteVersion = '';
        if (isset($_POST['submit_delete_version'])) {
            $deleteVersion = $this->tracking->deleteTrackingVersion($_POST['version']);
        }

        $createVersion = '';
        if (isset($_POST['submit_create_version'])) {
            $createVersion = $this->tracking->createTrackingVersion();
        }

        $deactivateTracking = '';
        if (isset($_POST['toggle_activation'])
            && $_POST['toggle_activation'] === 'deactivate_now'
        ) {
            $deactivateTracking = $this->tracking->changeTracking('deactivate');
        }

        $activateTracking = '';
        if (isset($_POST['toggle_activation'])
            && $_POST['toggle_activation'] === 'activate_now'
        ) {
            $activateTracking = $this->tracking->changeTracking('activate');
        }

        // Export as SQL execution
        $message = '';
        if (isset($_POST['report_export']) && $_POST['export_type'] === 'execution') {
            $sql_result = $this->tracking->exportAsSqlExecution($entries);
            $msg = Message::success(__('SQL statements executed.'));
            $message = $msg->getDisplay();
        }

        $sqlDump = '';
        if (isset($_POST['report_export']) && $_POST['export_type'] === 'sqldump') {
            $sqlDump = $this->tracking->exportAsSqlDump($entries);
        }

        $schemaSnapshot = '';
        if (isset($_POST['snapshot'])) {
            $schemaSnapshot = $this->tracking->getHtmlForSchemaSnapshot($url_params);
        }

        $trackingReportRows = '';
        if (isset($_POST['report'])
            && (isset($_POST['delete_ddlog']) || isset($_POST['delete_dmlog']))
        ) {
            $trackingReportRows = $this->tracking->deleteTrackingReportRows($data);
        }

        $trackingReport = '';
        if (isset($_POST['report']) || isset($_POST['report_export'])) {
            $trackingReport = $this->tracking->getHtmlForTrackingReport(
                $data,
                $url_params,
                $selection_schema,
                $selection_data,
                $selection_both,
                (int) $filter_ts_to,
                (int) $filter_ts_from,
                $filter_users
            );
        }

        $main = $this->tracking->getHtmlForMainPage(
            $url_params,
            $PMA_Theme->getImgPath(),
            $text_dir
        );

        $this->render('table/tracking/index', [
            'active_message' => $activeMessage,
            'action_message' => $actionMessage,
            'delete_version' => $deleteVersion,
            'create_version' => $createVersion,
            'deactivate_tracking' => $deactivateTracking,
            'activate_tracking' => $activateTracking,
            'message' => $message,
            'sql_dump' => $sqlDump,
            'schema_snapshot' => $schemaSnapshot,
            'tracking_report_rows' => $trackingReportRows,
            'tracking_report' => $trackingReport,
            'main' => $main,
        ]);
    }
}
