<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use DateTimeImmutable;
use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
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
use function explode;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function mb_strlen;
use function sprintf;
use function trim;

final class TrackingController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Tracking $tracking,
        private readonly TrackingChecker $trackingChecker,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->response->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'table/tracking.js']);

        if (! $this->response->checkParameters(['db', 'table'])) {
            return null;
        }

        $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabTable'],
            'table',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return null;
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return null;
        }

        $activeMessage = '';
        $toggleActivation = $request->getParsedBodyParam('toggle_activation');
        $reportExportType = $request->getParsedBodyParam('export_type');

        $trackedTables = $this->trackingChecker->getTrackedTables(Current::$database);
        if (
            Tracker::isActive()
            && isset($trackedTables[Current::$table])
            && $trackedTables[Current::$table]->active
            && $toggleActivation !== 'deactivate_now'
            && $reportExportType !== 'sqldumpfile'
        ) {
            $activeMessage = Message::notice(
                sprintf(
                    __('Tracking of %s is activated.'),
                    htmlspecialchars(Current::$database . '.' . Current::$table),
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
            $trackedData = $this->tracking->getTrackedData(Current::$database, Current::$table, $versionParam);

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

                    return null;
                }

                // Export as SQL execution
                if ($reportExportType === 'execution') {
                    $this->tracking->exportAsSqlExecution($entries);
                    $message = Message::success(__('SQL statements executed.'))->getDisplay();
                } elseif ($reportExportType === 'sqldump') {
                    $this->response->addScriptFiles(['sql.js']);
                    $sqlDump = $this->tracking->exportAsSqlDump($entries);
                }
            }

            if ($request->hasBodyParam('delete_ddlog')) {
                $trackingReportRows = $this->tracking->deleteFromTrackingReportLog(
                    Current::$database,
                    Current::$table,
                    $versionParam,
                    $trackedData->ddlog,
                    LogTypeEnum::DDL,
                    (int) $request->getParsedBodyParam('delete_ddlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData(Current::$database, Current::$table, $versionParam);
            } elseif ($request->hasBodyParam('delete_dmlog')) {
                $trackingReportRows = $this->tracking->deleteFromTrackingReportLog(
                    Current::$database,
                    Current::$table,
                    $versionParam,
                    $trackedData->dmlog,
                    LogTypeEnum::DML,
                    (int) $request->getParsedBodyParam('delete_dmlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData(Current::$database, Current::$table, $versionParam);
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
                        $this->tracking->deleteTrackingVersion(Current::$database, Current::$table, $version);
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
            $deleteVersion = $this->tracking->deleteTrackingVersion(Current::$database, Current::$table, $versionParam);
        }

        if ($request->hasBodyParam('submit_create_version')) {
            $createVersion = $this->tracking->createTrackingVersion(Current::$database, Current::$table, $versionParam);
        }

        if ($toggleActivation === 'deactivate_now') {
            $deactivateTracking = $this->tracking->changeTracking(
                Current::$database,
                Current::$table,
                $versionParam,
                'deactivate',
            );
        } elseif ($toggleActivation === 'activate_now') {
            $activateTracking = $this->tracking->changeTracking(
                Current::$database,
                Current::$table,
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
            Current::$database,
            Current::$table,
            $GLOBALS['urlParams'],
            LanguageManager::$textDir,
        );

        $this->response->render('table/tracking/index', [
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

        return null;
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
