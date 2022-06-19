<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
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

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Tracking $tracking
    ) {
        parent::__construct($response, $template);
        $this->tracking = $tracking;
    }

    public function __invoke(): void
    {
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['msg'] = $GLOBALS['msg'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['data'] = $GLOBALS['data'] ?? null;
        $GLOBALS['entries'] = $GLOBALS['entries'] ?? null;
        $GLOBALS['filter_ts_from'] = $GLOBALS['filter_ts_from'] ?? null;
        $GLOBALS['filter_ts_to'] = $GLOBALS['filter_ts_to'] ?? null;
        $GLOBALS['filter_users'] = $GLOBALS['filter_users'] ?? null;
        $GLOBALS['selection_schema'] = $GLOBALS['selection_schema'] ?? null;
        $GLOBALS['selection_data'] = $GLOBALS['selection_data'] ?? null;
        $GLOBALS['selection_both'] = $GLOBALS['selection_both'] ?? null;

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'table/tracking.js']);

        define('TABLE_MAY_BE_ABSENT', true);

        $this->checkParameters(['db', 'table']);

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $activeMessage = '';
        if (
            Tracker::isActive()
            && Tracker::isTracked($GLOBALS['db'], $GLOBALS['table'])
            && ! (isset($_POST['toggle_activation'])
                && $_POST['toggle_activation'] === 'deactivate_now')
            && ! (isset($_POST['report_export'])
                && $_POST['export_type'] === 'sqldumpfile')
        ) {
            $GLOBALS['msg'] = Message::notice(
                sprintf(
                    __('Tracking of %s is activated.'),
                    htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
                )
            );
            $activeMessage = $GLOBALS['msg']->getDisplay();
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/tracking');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/tracking');

        $GLOBALS['data'] = [];
        $GLOBALS['entries'] = [];
        $GLOBALS['filter_ts_from'] = null;
        $GLOBALS['filter_ts_to'] = null;
        $GLOBALS['filter_users'] = [];
        $GLOBALS['selection_schema'] = false;
        $GLOBALS['selection_data'] = false;
        $GLOBALS['selection_both'] = false;

        // Init vars for tracking report
        if (isset($_POST['report']) || isset($_POST['report_export'])) {
            $GLOBALS['data'] = Tracker::getTrackedData($GLOBALS['db'], $GLOBALS['table'], $_POST['version']);

            if (! isset($_POST['logtype'])) {
                $_POST['logtype'] = 'schema_and_data';
            }

            if ($_POST['logtype'] === 'schema') {
                $GLOBALS['selection_schema'] = true;
            } elseif ($_POST['logtype'] === 'data') {
                $GLOBALS['selection_data'] = true;
            } else {
                $GLOBALS['selection_both'] = true;
            }

            if (! isset($_POST['date_from'])) {
                $_POST['date_from'] = $GLOBALS['data']['date_from'];
            }

            if (! isset($_POST['date_to'])) {
                $_POST['date_to'] = $GLOBALS['data']['date_to'];
            }

            if (! isset($_POST['users'])) {
                $_POST['users'] = '*';
            }

            $GLOBALS['filter_ts_from'] = strtotime($_POST['date_from']);
            $GLOBALS['filter_ts_to'] = strtotime($_POST['date_to']);
            $GLOBALS['filter_users'] = array_map('trim', explode(',', $_POST['users']));
        }

        // Prepare export
        if (isset($_POST['report_export'])) {
            $GLOBALS['entries'] = $this->tracking->getEntries(
                $GLOBALS['data'],
                (int) $GLOBALS['filter_ts_from'],
                (int) $GLOBALS['filter_ts_to'],
                $GLOBALS['filter_users']
            );
        }

        // Export as file download
        if (isset($_POST['report_export']) && $_POST['export_type'] === 'sqldumpfile') {
            $this->tracking->exportAsFileDownload($GLOBALS['entries']);
        }

        $actionMessage = '';
        if (isset($_POST['submit_mult'])) {
            if (! empty($_POST['selected_versions'])) {
                if ($_POST['submit_mult'] === 'delete_version') {
                    foreach ($_POST['selected_versions'] as $version) {
                        $this->tracking->deleteTrackingVersion($GLOBALS['db'], $GLOBALS['table'], $version);
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
            $deleteVersion = $this->tracking->deleteTrackingVersion(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $_POST['version']
            );
        }

        $createVersion = '';
        if (isset($_POST['submit_create_version'])) {
            $createVersion = $this->tracking->createTrackingVersion($GLOBALS['db'], $GLOBALS['table']);
        }

        $deactivateTracking = '';
        if (isset($_POST['toggle_activation']) && $_POST['toggle_activation'] === 'deactivate_now') {
            $deactivateTracking = $this->tracking->changeTracking($GLOBALS['db'], $GLOBALS['table'], 'deactivate');
        }

        $activateTracking = '';
        if (isset($_POST['toggle_activation']) && $_POST['toggle_activation'] === 'activate_now') {
            $activateTracking = $this->tracking->changeTracking($GLOBALS['db'], $GLOBALS['table'], 'activate');
        }

        // Export as SQL execution
        $message = '';
        if (isset($_POST['report_export']) && $_POST['export_type'] === 'execution') {
            $this->tracking->exportAsSqlExecution($GLOBALS['entries']);
            $GLOBALS['msg'] = Message::success(__('SQL statements executed.'));
            $message = $GLOBALS['msg']->getDisplay();
        }

        $sqlDump = '';
        if (isset($_POST['report_export']) && $_POST['export_type'] === 'sqldump') {
            $this->addScriptFiles(['sql.js']);
            $sqlDump = $this->tracking->exportAsSqlDump($GLOBALS['db'], $GLOBALS['table'], $GLOBALS['entries']);
        }

        $schemaSnapshot = '';
        if (isset($_POST['snapshot'])) {
            $schemaSnapshot = $this->tracking->getHtmlForSchemaSnapshot($GLOBALS['urlParams']);
        }

        $trackingReportRows = '';
        if (isset($_POST['report']) && (isset($_POST['delete_ddlog']) || isset($_POST['delete_dmlog']))) {
            $trackingReportRows = $this->tracking->deleteTrackingReportRows(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $GLOBALS['data']
            );
        }

        $trackingReport = '';
        if (isset($_POST['report']) || isset($_POST['report_export'])) {
            $trackingReport = $this->tracking->getHtmlForTrackingReport(
                $GLOBALS['data'],
                $GLOBALS['urlParams'],
                $GLOBALS['selection_schema'],
                $GLOBALS['selection_data'],
                $GLOBALS['selection_both'],
                (int) $GLOBALS['filter_ts_to'],
                (int) $GLOBALS['filter_ts_from'],
                $GLOBALS['filter_users']
            );
        }

        $main = $this->tracking->getHtmlForMainPage(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['urlParams'],
            $GLOBALS['text_dir']
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
