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
use PhpMyAdmin\Tracking\LogTypeEnum;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
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
use function trim;

final class TrackingController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Tracking $tracking,
        private TrackingChecker $trackingChecker,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['text_dir'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'table/tracking.js']);

        define('TABLE_MAY_BE_ABSENT', true);

        $this->checkParameters(['db', 'table']);

        $GLOBALS['urlParams'] = ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabTable'], 'table');
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        DbTableExists::check($GLOBALS['db'], $GLOBALS['table']);

        $activeMessage = '';
        $toggleActivation = $request->getParsedBodyParam('toggle_activation');
        $reportExportType = $request->getParsedBodyParam('export_type');

        $trackedTables = $this->trackingChecker->getTrackedTables($GLOBALS['db']);
        if (
            Tracker::isActive()
            && isset($trackedTables[$GLOBALS['table']])
            && $trackedTables[$GLOBALS['table']]->active
            && $toggleActivation !== 'deactivate_now'
            && $reportExportType !== 'sqldumpfile'
        ) {
            $activeMessage = Message::notice(
                sprintf(
                    __('Tracking of %s is activated.'),
                    htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table']),
                ),
            )->getDisplay();
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/tracking');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/table/tracking');

        /** @var string $versionParam */
        $versionParam = $request->getParsedBodyParam('version');
        /** @var string $tableParam */
        $tableParam = $request->getParsedBodyParam('table');

        $logType = $this->validateLogTypeParam($request->getParsedBodyParam('log_type'));

        $message = '';
        $sqlDump = '';
        $deleteVersion = '';
        $createVersion = '';
        $deactivateTracking = '';
        $activateTracking = '';
        $schemaSnapshot = '';
        $trackingReportRows = '';
        $trackingReport = '';

        // Init vars for tracking report
        if ($request->hasBodyParam('report')) {
            $trackedData = $this->tracking->getTrackedData($GLOBALS['db'], $GLOBALS['table'], $versionParam);

            $dateFrom = $this->validateDateTimeParam(
                $request->getParsedBodyParam('date_from', $trackedData->dateFrom),
            );
            $dateTo = $this->validateDateTimeParam($request->getParsedBodyParam('date_to', $trackedData->dateTo));

            /** @var string $users */
            $users = $request->getParsedBodyParam('users', '*');

            $filterUsers = array_map(trim(...), explode(',', $users));

            // Prepare export
            if ($reportExportType !== null) {
                $entries = $this->tracking->getEntries($trackedData, $filterUsers, $logType, $dateFrom, $dateTo);

                // Export as file download
                if ($reportExportType === 'sqldumpfile') {
                    $downloadInfo = $this->tracking->getDownloadInfoForExport($tableParam, $entries);
                    $this->response->disable();
                    Core::downloadHeader($downloadInfo['filename'], 'text/x-sql', mb_strlen($downloadInfo['dump']));
                    echo $downloadInfo['dump'];

                    return;
                }

                // Export as SQL execution
                if ($reportExportType === 'execution') {
                    $this->tracking->exportAsSqlExecution($entries);
                    $message = Message::success(__('SQL statements executed.'))->getDisplay();
                } elseif ($reportExportType === 'sqldump') {
                    $this->addScriptFiles(['sql.js']);
                    $sqlDump = $this->tracking->exportAsSqlDump($entries);
                }
            }

            if ($request->hasBodyParam('delete_ddlog')) {
                $trackingReportRows = $this->tracking->deleteFromTrackingReportLog(
                    $GLOBALS['db'],
                    $GLOBALS['table'],
                    $versionParam,
                    $trackedData->ddlog,
                    LogTypeEnum::DDL,
                    (int) $request->getParsedBodyParam('delete_ddlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData($GLOBALS['db'], $GLOBALS['table'], $versionParam);
            } elseif ($request->hasBodyParam('delete_dmlog')) {
                $trackingReportRows = $this->tracking->deleteFromTrackingReportLog(
                    $GLOBALS['db'],
                    $GLOBALS['table'],
                    $versionParam,
                    $trackedData->dmlog,
                    LogTypeEnum::DML,
                    (int) $request->getParsedBodyParam('delete_dmlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData($GLOBALS['db'], $GLOBALS['table'], $versionParam);
            }

            $trackingReport = $this->tracking->getHtmlForTrackingReport(
                $trackedData,
                $GLOBALS['urlParams'],
                $logType,
                $filterUsers,
                $versionParam,
                $dateFrom,
                $dateTo,
                $users,
            );
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

        if ($request->hasBodyParam('submit_delete_version')) {
            $deleteVersion = $this->tracking->deleteTrackingVersion($GLOBALS['db'], $GLOBALS['table'], $versionParam);
        }

        if ($request->hasBodyParam('submit_create_version')) {
            $createVersion = $this->tracking->createTrackingVersion($GLOBALS['db'], $GLOBALS['table'], $versionParam);
        }

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

    /** @psalm-return 'schema'|'data'|'schema_and_data' */
    private function validateLogTypeParam(mixed $param): string
    {
        return in_array($param, ['schema', 'data'], true) ? $param : 'schema_and_data';
    }

    private function validateDateTimeParam(mixed $param): DateTimeImmutable
    {
        try {
            Assert::stringNotEmpty($param);

            return new DateTimeImmutable($param);
        } catch (Throwable) {
            return new DateTimeImmutable();
        }
    }
}
