<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
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
use function is_array;
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

    public function __invoke(ServerRequest $request): void
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
        $toggleActivation = $request->getParsedBodyParam('toggle_activation');
        $reportExport = $request->getParsedBodyParam('report_export');

        if (
            Tracker::isActive()
            && Tracker::isTracked($GLOBALS['db'], $GLOBALS['table'])
            && $toggleActivation !== 'deactivate_now'
            && $reportExport !== 'sqldumpfile'
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

        $report = $request->hasBodyParam('report');
        /** @var string $versionParam */
        $versionParam = $request->getParsedBodyParam('version');
        /** @var string $tableParam */
        $tableParam = $request->getParsedBodyParam('table');

        // Init vars for tracking report
        if ($report || $reportExport !== null) {
            $GLOBALS['data'] = Tracker::getTrackedData(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam
            );

            $logType = $request->getParsedBodyParam('logtype', 'schema_and_data');

            if ($logType === 'schema') {
                $GLOBALS['selection_schema'] = true;
            } elseif ($logType === 'data') {
                $GLOBALS['selection_data'] = true;
            } else {
                $GLOBALS['selection_both'] = true;
            }

            /** @var string $dateFrom */
            $dateFrom = $request->getParsedBodyParam('date_from', $GLOBALS['data']['date_from']);
            /** @var string $dateTo */
            $dateTo = $request->getParsedBodyParam('date_to', $GLOBALS['data']['date_to']);
            /** @var string $users */
            $users = $request->getParsedBodyParam('users', '*');

            $GLOBALS['filter_ts_from'] = strtotime($dateFrom);
            $GLOBALS['filter_ts_to'] = strtotime($dateTo);
            $GLOBALS['filter_users'] = array_map('trim', explode(',', $users));
        }

        // Prepare export
        if ($reportExport !== null) {
            $GLOBALS['entries'] = $this->tracking->getEntries(
                $GLOBALS['data'],
                (int) $GLOBALS['filter_ts_from'],
                (int) $GLOBALS['filter_ts_to'],
                $GLOBALS['filter_users'],
                $_POST['logtype']
            );
        }

        // Export as file download
        if ($reportExport !== null && $request->getParsedBodyParam('export_type') === 'sqldumpfile') {
            $this->tracking->exportAsFileDownload($tableParam, $GLOBALS['entries']);
        }

        $actionMessage = '';
        $submitMult = $request->getParsedBodyParam('submit_mult');
        $selectedVersions = $request->getParsedBodyParam('selected_versions');
        if ($submitMult !== null) {
            if (is_array($selectedVersions) && $selectedVersions !== []) {
                if ($submitMult === 'delete_version') {
                    foreach ($selectedVersions as $version) {
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
        if ($request->hasBodyParam('submit_delete_version')) {
            $deleteVersion = $this->tracking->deleteTrackingVersion(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam
            );
        }

        $createVersion = '';
        if ($request->hasBodyParam('submit_create_version')) {
            $createVersion = $this->tracking->createTrackingVersion(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam
            );
        }

        $deactivateTracking = '';
        $activateTracking = '';

        if ($toggleActivation === 'deactivate_now') {
            $deactivateTracking = $this->tracking->changeTracking(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                'deactivate'
            );
        } elseif ($toggleActivation === 'activate_now') {
            $activateTracking = $this->tracking->changeTracking(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                'activate'
            );
        }

        // Export as SQL execution
        $message = '';
        $sqlDump = '';

        if ($reportExport === 'execution') {
            $this->tracking->exportAsSqlExecution($GLOBALS['entries']);
            $GLOBALS['msg'] = Message::success(__('SQL statements executed.'));
            $message = $GLOBALS['msg']->getDisplay();
        } elseif ($reportExport === 'sqldump') {
            $this->addScriptFiles(['sql.js']);
            $sqlDump = $this->tracking->exportAsSqlDump($GLOBALS['db'], $GLOBALS['table'], $GLOBALS['entries']);
        }

        $schemaSnapshot = '';
        if ($request->hasBodyParam('snapshot')) {
            /** @var string $db */
            $db = $request->getParsedBodyParam('db');
            $schemaSnapshot = $this->tracking->getHtmlForSchemaSnapshot(
                $db,
                $tableParam,
                $versionParam,
                $GLOBALS['urlParams']
            );
        }

        $trackingReportRows = '';
        if ($report && ($request->hasBodyParam('delete_ddlog') || $request->hasBodyParam('delete_dmlog'))) {
            $trackingReportRows = $this->tracking->deleteTrackingReportRows(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                $GLOBALS['data'],
                isset($_POST['delete_ddlog']),
                isset($_POST['delete_dmlog'])
            );
        }

        $trackingReport = '';
        if ($report || $reportExport !== null) {
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
