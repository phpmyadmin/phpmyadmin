<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
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

    public function __invoke(): void
    {
        $GLOBALS['text_dir'] = $GLOBALS['text_dir'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['data'] = $GLOBALS['data'] ?? null;
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

        // Get the database structure
        $GLOBALS['sub_part'] = '_structure';

        [
            $GLOBALS['tables'],
            $GLOBALS['num_tables'],
            $GLOBALS['total_num_tables'],
            $GLOBALS['sub_part'],,
            $isSystemSchema,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part']);

        if (isset($_POST['delete_tracking'], $_POST['table'])) {
            Tracker::deleteTracking($GLOBALS['db'], $_POST['table']);
            echo Message::success(
                __('Tracking data deleted successfully.')
            )->getDisplay();
        } elseif (isset($_POST['submit_create_version'])) {
            $this->tracking->createTrackingForMultipleTables($GLOBALS['db'], $_POST['selected']);
            echo Message::success(
                sprintf(
                    __(
                        'Version %1$s was created for selected tables, tracking is active for them.'
                    ),
                    htmlspecialchars($_POST['version'])
                )
            )->getDisplay();
        } elseif (isset($_POST['submit_mult'])) {
            if (! empty($_POST['selected_tbl'])) {
                if ($_POST['submit_mult'] === 'delete_tracking') {
                    foreach ($_POST['selected_tbl'] as $table) {
                        Tracker::deleteTracking($GLOBALS['db'], $table);
                    }

                    echo Message::success(
                        __('Tracking data deleted successfully.')
                    )->getDisplay();
                } elseif ($_POST['submit_mult'] === 'track') {
                    echo $this->template->render('create_tracking_version', [
                        'route' => '/database/tracking',
                        'url_params' => $GLOBALS['urlParams'],
                        'last_version' => 0,
                        'db' => $GLOBALS['db'],
                        'selected' => $_POST['selected_tbl'],
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
        $GLOBALS['data'] = Tracker::getTrackedData($GLOBALS['db'], '', '1');

        // No tables present and no log exist
        if ($GLOBALS['num_tables'] == 0 && count($GLOBALS['data']['ddlog']) === 0) {
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
        if (count($GLOBALS['data']['ddlog']) <= 0) {
            return;
        }

        $log = '';
        foreach ($GLOBALS['data']['ddlog'] as $entry) {
            $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
                . $entry['statement'] . "\n";
        }

        echo Generator::getMessage(__('Database Log'), $log);
    }
}
