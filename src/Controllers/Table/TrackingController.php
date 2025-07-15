<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use DateTimeImmutable;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Tracking\LogType;
use PhpMyAdmin\Tracking\TrackedDataType;
use PhpMyAdmin\Tracking\Tracker;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Tracking\TrackingChecker;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use Throwable;
use Webmozart\Assert\Assert;

use function __;
use function array_map;
use function explode;
use function htmlspecialchars;
use function is_array;
use function mb_strlen;
use function sprintf;
use function trim;

#[Route('/table/tracking', ['GET', 'POST'])]
final class TrackingController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly Tracking $tracking,
        private readonly TrackingChecker $trackingChecker,
        private readonly DbTableExists $dbTableExists,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'table/tracking.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        UrlParams::$params = ['db' => Current::$database, 'table' => Current::$table];

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
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

        UrlParams::$params['goto'] = Url::getFromRoute('/table/tracking');
        UrlParams::$params['back'] = Url::getFromRoute('/table/tracking');

        $versionParam = $request->getParsedBodyParamAsString('version', '');
        $tableParam = $request->getParsedBodyParamAsString('table', '');

        $logType = LogType::tryFrom($request->getParsedBodyParamAsString('log_type', '')) ?? LogType::SchemaAndData;

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

            $users = $request->getParsedBodyParamAsString('users', '*');

            $filterUsers = array_map(trim(...), explode(',', $users));

            // Prepare export
            if ($reportExportType !== null) {
                $entries = $this->tracking->getEntries($trackedData, $filterUsers, $logType, $dateFrom, $dateTo);

                // Export as file download
                if ($reportExportType === 'sqldumpfile') {
                    $downloadInfo = $this->tracking->getDownloadInfoForExport($tableParam, $entries);
                    $response = $this->responseFactory->createResponse();
                    Core::downloadHeader($downloadInfo['filename'], 'text/x-sql', mb_strlen($downloadInfo['dump']));

                    return $response->write($downloadInfo['dump']);
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
                    TrackedDataType::DDL,
                    (int) $request->getParsedBodyParamAsStringOrNull('delete_ddlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData(Current::$database, Current::$table, $versionParam);
            } elseif ($request->hasBodyParam('delete_dmlog')) {
                $trackingReportRows = $this->tracking->deleteFromTrackingReportLog(
                    Current::$database,
                    Current::$table,
                    $versionParam,
                    $trackedData->dmlog,
                    TrackedDataType::DML,
                    (int) $request->getParsedBodyParamAsStringOrNull('delete_dmlog'),
                );
                // After deletion reload data from the database
                $trackedData = $this->tracking->getTrackedData(Current::$database, Current::$table, $versionParam);
            }

            $trackingReport = $this->tracking->getHtmlForTrackingReport(
                $trackedData,
                UrlParams::$params,
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
            $db = $request->getParsedBodyParamAsString('db');
            $schemaSnapshot = $this->tracking->getHtmlForSchemaSnapshot(
                $db,
                $tableParam,
                $versionParam,
                UrlParams::$params,
            );
        }

        $main = $this->tracking->getHtmlForMainPage(Current::$database, Current::$table, UrlParams::$params);

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

        return $this->response->response();
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
