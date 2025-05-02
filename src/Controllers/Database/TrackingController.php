<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;

use function __;
use function htmlspecialchars;
use function sprintf;

/**
 * Tracking configuration for database.
 */
final readonly class TrackingController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Tracking $tracking,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->response->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'database/tracking.js']);

        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return $this->response->response();
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/table/tracking');
        UrlParams::$params['back'] = Url::getFromRoute('/database/tracking');

        $isSystemSchema = Utilities::isSystemSchema(Current::$database);

        if ($request->hasBodyParam('delete_tracking') && $request->hasBodyParam('table')) {
            $this->tracking->deleteTracking(Current::$database, $request->getParsedBodyParamAsString('table'));
            $this->response->addHTML(Message::success(
                __('Tracking data deleted successfully.'),
            )->getDisplay());
        } elseif ($request->hasBodyParam('submit_create_version')) {
            $this->tracking->createTrackingForMultipleTables(
                Current::$database,
                $request->getParsedBodyParam('selected'),
                $request->getParsedBodyParamAsString('version'),
            );
            $this->response->addHTML(Message::success(
                sprintf(
                    __(
                        'Version %1$s was created for selected tables, tracking is active for them.',
                    ),
                    htmlspecialchars($request->getParsedBodyParamAsString('version')),
                ),
            )->getDisplay());
        } elseif ($request->hasBodyParam('submit_mult')) {
        /** @var string[] $selectedTable */
            $selectedTable = $request->getParsedBodyParam('selected_tbl', []);
            if ($selectedTable !== []) {
                if ($request->getParsedBodyParam('submit_mult') === 'delete_tracking') {
                    foreach ($selectedTable as $table) {
                        $this->tracking->deleteTracking(Current::$database, $table);
                    }

                    $this->response->addHTML(Message::success(
                        __('Tracking data deleted successfully.'),
                    )->getDisplay());
                } elseif ($request->getParsedBodyParam('submit_mult') === 'track') {
                    $this->response->render('create_tracking_version', [
                        'route' => '/database/tracking',
                        'url_params' => UrlParams::$params,
                        'last_version' => 0,
                        'db' => Current::$database,
                        'selected' => $selectedTable,
                        'type' => 'both',
                        'default_statements' => $this->config->selectedServer['tracking_default_statements'],
                    ]);

                    return $this->response->response();
                }
            } else {
                $this->response->addHTML(Message::notice(
                    __('No tables selected.'),
                )->getDisplay());
            }
        }

        // Get tracked data about the database
        $trackedData = $this->tracking->getTrackedData(Current::$database, '', '1');

        // No tables present and no log exist
        if ($trackedData->ddlog === [] && $this->dbi->getTables(Current::$database) === []) {
            $this->response->addHTML('<p>' . __('No tables found in database.') . '</p>' . "\n");

            if (! $isSystemSchema) {
                $this->response->render('database/create_table', ['db' => Current::$database]);
            }

            return $this->response->response();
        }

        $this->response->addHTML($this->tracking->getHtmlForDbTrackingTables(
            Current::$database,
            UrlParams::$params,
        ));

        // If available print out database log
        if ($trackedData->ddlog === []) {
            return $this->response->response();
        }

        $log = '';
        foreach ($trackedData->ddlog as $entry) {
            $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
                . $entry['statement'] . "\n";
        }

        $this->response->addHTML(Generator::getMessage(__('Database Log'), $log));

        return $this->response->response();
    }
}
