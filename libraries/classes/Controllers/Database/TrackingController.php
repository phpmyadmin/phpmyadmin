<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracking\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function sprintf;

/**
 * Tracking configuration for database.
 */
class TrackingController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Tracking $tracking,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['text_dir'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'database/tracking.js']);

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/tracking');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/database/tracking');

        [, $numTables] = Util::getDbInfo($request, $GLOBALS['db']);
        $isSystemSchema = Utilities::isSystemSchema($GLOBALS['db']);

        if ($request->hasBodyParam('delete_tracking') && $request->hasBodyParam('table')) {
            $this->tracking->deleteTracking($GLOBALS['db'], $request->getParsedBodyParam('table'));
            echo Message::success(
                __('Tracking data deleted successfully.'),
            )->getDisplay();
        } elseif ($request->hasBodyParam('submit_create_version')) {
            $this->tracking->createTrackingForMultipleTables(
                $GLOBALS['db'],
                $request->getParsedBodyParam('selected'),
                $request->getParsedBodyParam('version'),
            );
            echo Message::success(
                sprintf(
                    __(
                        'Version %1$s was created for selected tables, tracking is active for them.',
                    ),
                    htmlspecialchars($request->getParsedBodyParam('version')),
                ),
            )->getDisplay();
        } elseif ($request->hasBodyParam('submit_mult')) {
            $selectedTable = $request->getParsedBodyParam('selected_tbl');
            if (! empty($selectedTable)) {
                if ($request->getParsedBodyParam('submit_mult') === 'delete_tracking') {
                    foreach ($selectedTable as $table) {
                        $this->tracking->deleteTracking($GLOBALS['db'], $table);
                    }

                    echo Message::success(
                        __('Tracking data deleted successfully.'),
                    )->getDisplay();
                } elseif ($request->getParsedBodyParam('submit_mult') === 'track') {
                    echo $this->template->render('create_tracking_version', [
                        'route' => '/database/tracking',
                        'url_params' => $GLOBALS['urlParams'],
                        'last_version' => 0,
                        'db' => $GLOBALS['db'],
                        'selected' => $selectedTable,
                        'type' => 'both',
                        'default_statements' => $GLOBALS['cfg']['Server']['tracking_default_statements'],
                    ]);

                    return;
                }
            } else {
                echo Message::notice(
                    __('No tables selected.'),
                )->getDisplay();
            }
        }

        // Get tracked data about the database
        $trackedData = $this->tracking->getTrackedData($GLOBALS['db'], '', '1');

        // No tables present and no log exist
        if ($numTables === 0 && $trackedData->ddlog === []) {
            echo '<p>' , __('No tables found in database.') , '</p>' , "\n";

            if (! $isSystemSchema) {
                $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
                $checkUserPrivileges->getPrivileges();

                echo $this->template->render('database/create_table', ['db' => $GLOBALS['db']]);
            }

            return;
        }

        echo $this->tracking->getHtmlForDbTrackingTables($GLOBALS['db'], $GLOBALS['urlParams'], $GLOBALS['text_dir']);

        // If available print out database log
        if ($trackedData->ddlog === []) {
            return;
        }

        $log = '';
        foreach ($trackedData->ddlog as $entry) {
            $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
                . $entry['statement'] . "\n";
        }

        echo Generator::getMessage(__('Database Log'), $log);
    }
}
