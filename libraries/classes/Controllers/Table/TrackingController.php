<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use DateTimeImmutable;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Throwable;
use Webmozart\Assert\Assert;

use function __;
use function array_map;
use function define;
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function mb_strlen;
use function sprintf;

final class TrackingController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Tracking $tracking,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['text_dir'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['msg'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['entries'] ??= null;
        $GLOBALS['filter_users'] ??= null;

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
                    htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                ),
            );
            $activeMessage = $GLOBALS['msg']->getDisplay();
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/tracking');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/tracking');

        $trackedData = [];
        $GLOBALS['entries'] = [];
        $GLOBALS['filter_users'] = [];

        $report = $request->hasBodyParam('report');
        /** @var string $versionParam */
        $versionParam = $request->getParsedBodyParam('version');
        /** @var string $tableParam */
        $tableParam = $request->getParsedBodyParam('table');

        $logType = $this->validateLogTypeParam($request->getParsedBodyParam('log_type'));

        $dateFrom = null;
        $dateTo = null;
        $users = '';

        // Init vars for tracking report
        if ($report || $reportExport !== null) {
            $trackedData = Tracker::getTrackedData(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
            );

            $dateFrom = $this->validateDateTimeParam(
                $request->getParsedBodyParam('date_from', $trackedData['date_from']),
            );
            $dateTo = $this->validateDateTimeParam($request->getParsedBodyParam('date_to', $trackedData['date_to']));

            /** @var string $users */
            $users = $request->getParsedBodyParam('users', '*');

            $GLOBALS['filter_users'] = array_map('trim', explode(',', $users));
        }

        $dateFrom ??= new DateTimeImmutable();
        $dateTo ??= new DateTimeImmutable();

        // Prepare export
        if ($reportExport !== null) {
            $GLOBALS['entries'] = $this->tracking->getEntries(
                $trackedData,
                $GLOBALS['filter_users'],
                $logType,
                $dateFrom,
                $dateTo,
            );
        }

        // Export as file download
        if ($reportExport !== null && $request->getParsedBodyParam('export_type') === 'sqldumpfile') {
            $downloadInfo = $this->tracking->getDownloadInfoForExport($tableParam, $GLOBALS['entries']);
            $this->response->disable();
            Core::downloadHeader($downloadInfo['filename'], 'text/x-sql', mb_strlen($downloadInfo['dump']));
            echo $downloadInfo['dump'];

            return;
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
                        __('Tracking versions deleted successfully.'),
                    )->getDisplay();
                }
            } else {
                $actionMessage = Message::notice(
                    __('No versions selected.'),
                )->getDisplay();
            }
        }

        $deleteVersion = '';
        if ($request->hasBodyParam('submit_delete_version')) {
            $deleteVersion = $this->tracking->deleteTrackingVersion(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
            );
        }

        $createVersion = '';
        if ($request->hasBodyParam('submit_create_version')) {
            $createVersion = $this->tracking->createTrackingVersion(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
            );
        }

        $deactivateTracking = '';
        $activateTracking = '';

        if ($toggleActivation === 'deactivate_now') {
            $deactivateTracking = $this->tracking->changeTracking(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                'deactivate',
            );
        } elseif ($toggleActivation === 'activate_now') {
            $activateTracking = $this->tracking->changeTracking(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                'activate',
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
                $GLOBALS['urlParams'],
            );
        }

        $trackingReportRows = '';
        if ($report && ($request->hasBodyParam('delete_ddlog') || $request->hasBodyParam('delete_dmlog'))) {
            $trackingReportRows = $this->tracking->deleteTrackingReportRows(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $versionParam,
                $trackedData,
                $request->hasBodyParam('delete_ddlog'),
                $request->hasBodyParam('delete_dmlog'),
            );
        }

        $trackingReport = '';
        if ($report || $reportExport !== null) {
            $trackingReport = $this->tracking->getHtmlForTrackingReport(
                $trackedData,
                $GLOBALS['urlParams'],
                $logType,
                $GLOBALS['filter_users'],
                $versionParam,
                $dateFrom,
                $dateTo,
                $users,
            );
        }

        $main = $this->tracking->getHtmlForMainPage(
            $GLOBALS['db'],
            $GLOBALS['table'],
            $GLOBALS['urlParams'],
            $GLOBALS['text_dir'],
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

    /**
     * @param mixed $param
     *
     * @psalm-return 'schema'|'data'|'schema_and_data'
     */
    private function validateLogTypeParam($param): string
    {
        return in_array($param, ['schema', 'data'], true) ? $param : 'schema_and_data';
    }

    /** @param mixed $param */
    private function validateDateTimeParam($param): DateTimeImmutable
    {
        try {
            Assert::stringNotEmpty($param);

            return new DateTimeImmutable($param);
        } catch (Throwable) {
            return new DateTimeImmutable();
        }
    }
}
