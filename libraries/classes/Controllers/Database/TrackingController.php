<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function htmlspecialchars;
use function sprintf;

/**
 * Tracking configuration for database.
 */
class TrackingController extends AbstractController
{
    /** @var Tracking */
    private $tracking;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Tracking $tracking,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->tracking = $tracking;
        $this->dbi = $dbi;
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;

        $this->addScriptFiles(['vendor/jquery/jquery.tablesorter.js', 'database/tracking.js']);

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/table/tracking');
        $GLOBALS['urlParams']['back'] = Url::getFromRoute('/database/tracking');

        [
            $GLOBALS['tables'],
            $GLOBALS['num_tables'],
            $GLOBALS['total_num_tables'],,
            $isSystemSchema,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($request, $GLOBALS['db']);

        if ($request->hasBodyParam('delete_tracking') && $request->hasBodyParam('table')) {
            Tracker::deleteTracking($GLOBALS['db'], $request->getParsedBodyParam('table'));
            echo Message::success(
                __('Tracking data deleted successfully.')
            )->getDisplay();
        } elseif ($request->hasBodyParam('submit_create_version')) {
            $this->tracking->createTrackingForMultipleTables(
                $GLOBALS['db'], 
                $request->getParsedBodyParam('selected'), 
                $request->getParsedBodyParam('version')
            );
            echo Message::success(
                sprintf(
                    __(
                        'Version %1$s was created for selected tables, tracking is active for them.'
                    ),
                    htmlspecialchars($request->getParsedBodyParam('version'))
                )
            )->getDisplay();
        } elseif ($request->hasBodyParam('submit_mult')) {
            if ($request->hasBodyParam('selected_tbl')) {
                if ($request->getParsedBodyParam('submit_mult') === 'delete_tracking') {
                    foreach ($request->getParsedBodyParam('selected_tbl') as $table) {
                        Tracker::deleteTracking($GLOBALS['db'], $table);
                    }

                    echo Message::success(
                        __('Tracking data deleted successfully.')
                    )->getDisplay();
                } elseif ($request->getParsedBodyParam('submit_mult') === 'track') {
                    echo $this->template->render('create_tracking_version', [
                        'route' => '/database/tracking',
                        'url_params' => $GLOBALS['urlParams'],
                        'last_version' => 0,
                        'db' => $GLOBALS['db'],
                        'selected' => $request->getParsedBodyParam('selected_tbl'),
                        'type' => 'both',
                        'default_statements' => $GLOBALS['cfg']['Server']['tracking_default_statements'],
                    ]);

                    return;
                }
            } else {
                echo Message::notice(
                    __('No tables selected.')
                )->getDisplay();
            }
        }

        // Get tracked data about the database
        $trackedData = Tracker::getTrackedData($GLOBALS['db'], '', '1');

        // No tables present and no log exist
        if ($GLOBALS['num_tables'] == 0 && count($trackedData['ddlog']) === 0) {
            echo '<p>' , __('No tables found in database.') , '</p>' , "\n";

            if (empty($isSystemSchema)) {
                $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
                $checkUserPrivileges->getPrivileges();

                echo $this->template->render('database/create_table', ['db' => $GLOBALS['db']]);
            }

            return;
        }

        echo $this->tracking->getHtmlForDbTrackingTables($GLOBALS['db'], $GLOBALS['urlParams'], $GLOBALS['text_dir']);

        // If available print out database log
        if (count($trackedData['ddlog']) <= 0) {
            return;
        }

        $log = '';
        foreach ($trackedData['ddlog'] as $entry) {
            $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
                . $entry['statement'] . "\n";
        }

        echo Generator::getMessage(__('Database Log'), $log);
    }
}
