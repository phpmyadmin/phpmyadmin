<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
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

    /**
     * @param Response          $response A Response instance.
     * @param DatabaseInterface $dbi      A DatabaseInterface instance.
     * @param Template          $template A Template instance.
     * @param string            $db       Database name.
     * @param string            $table    Table name.
     * @param Tracking          $tracking A Tracking instance.
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Tracking $tracking
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->tracking = $tracking;
    }

    public function index(): void
    {
        global $pmaThemeImage, $text_dir, $url_query, $url_params, $msg;
        global $data, $entries, $filter_ts_from, $filter_ts_to, $filter_users, $selection_schema;
        global $selection_data, $selection_both, $sql_result;

        //Get some js files needed for Ajax requests
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('vendor/jquery/jquery.tablesorter.js');
        $scripts->addFile('table/tracking.js');

        define('TABLE_MAY_BE_ABSENT', true);
        Common::table();

        if (Tracker::isActive()
            && Tracker::isTracked($GLOBALS['db'], $GLOBALS['table'])
            && ! (isset($_POST['toggle_activation'])
                && $_POST['toggle_activation'] == 'deactivate_now')
            && ! (isset($_POST['report_export'])
                && $_POST['export_type'] == 'sqldumpfile')
        ) {
            $msg = Message::notice(
                sprintf(
                    __('Tracking of %s is activated.'),
                    htmlspecialchars($GLOBALS['db'] . '.' . $GLOBALS['table'])
                )
            );
            $this->response->addHTML($msg->getDisplay());
        }

        $url_params['goto'] = Url::getFromRoute('/table/tracking');
        $url_params['back'] = Url::getFromRoute('/table/tracking');
        $url_query .= Url::getCommon($url_params, '&');

        $data = [];
        $entries = [];
        $filter_ts_from = null;
        $filter_ts_to = null;
        $filter_users = [];
        $selection_schema = false;
        $selection_data = false;
        $selection_both = false;

        // Init vars for tracking report
        if (isset($_POST['report']) || isset($_POST['report_export'])) {
            $data = Tracker::getTrackedData(
                $GLOBALS['db'],
                $GLOBALS['table'],
                $_POST['version']
            );

            if (! isset($_POST['logtype'])) {
                $_POST['logtype'] = 'schema_and_data';
            }
            if ($_POST['logtype'] == 'schema') {
                $selection_schema = true;
            } elseif ($_POST['logtype'] == 'data') {
                $selection_data   = true;
            } else {
                $selection_both   = true;
            }
            if (! isset($_POST['date_from'])) {
                $_POST['date_from'] = $data['date_from'];
            }
            if (! isset($_POST['date_to'])) {
                $_POST['date_to'] = $data['date_to'];
            }
            if (! isset($_POST['users'])) {
                $_POST['users'] = '*';
            }
            $filter_ts_from = strtotime($_POST['date_from']);
            $filter_ts_to = strtotime($_POST['date_to']);
            $filter_users = array_map('trim', explode(',', $_POST['users']));
        }

        // Prepare export
        if (isset($_POST['report_export'])) {
            $entries = $this->tracking->getEntries(
                $data,
                (int) $filter_ts_from,
                (int) $filter_ts_to,
                $filter_users
            );
        }

        // Export as file download
        if (isset($_POST['report_export'])
            && $_POST['export_type'] == 'sqldumpfile'
        ) {
            $this->tracking->exportAsFileDownload($entries);
        }

        $html = '<br>';

        /**
         * Actions
         */
        if (isset($_POST['submit_mult'])) {
            if (! empty($_POST['selected_versions'])) {
                if ($_POST['submit_mult'] == 'delete_version') {
                    foreach ($_POST['selected_versions'] as $version) {
                        $this->tracking->deleteTrackingVersion($version);
                    }
                    $html .= Message::success(
                        __('Tracking versions deleted successfully.')
                    )->getDisplay();
                }
            } else {
                $html .= Message::notice(
                    __('No versions selected.')
                )->getDisplay();
            }
        }

        if (isset($_POST['submit_delete_version'])) {
            $html .= $this->tracking->deleteTrackingVersion($_POST['version']);
        }

        // Create tracking version
        if (isset($_POST['submit_create_version'])) {
            $html .= $this->tracking->createTrackingVersion();
        }

        // Deactivate tracking
        if (isset($_POST['toggle_activation'])
            && $_POST['toggle_activation'] == 'deactivate_now'
        ) {
            $html .= $this->tracking->changeTracking('deactivate');
        }

        // Activate tracking
        if (isset($_POST['toggle_activation'])
            && $_POST['toggle_activation'] == 'activate_now'
        ) {
            $html .= $this->tracking->changeTracking('activate');
        }

        // Export as SQL execution
        if (isset($_POST['report_export']) && $_POST['export_type'] == 'execution') {
            $sql_result = $this->tracking->exportAsSqlExecution($entries);
            $msg = Message::success(__('SQL statements executed.'));
            $html .= $msg->getDisplay();
        }

        // Export as SQL dump
        if (isset($_POST['report_export']) && $_POST['export_type'] == 'sqldump') {
            $html .= $this->tracking->exportAsSqlDump($entries);
        }

        /**
         * Schema snapshot
         */
        if (isset($_POST['snapshot'])) {
            $html .= $this->tracking->getHtmlForSchemaSnapshot($url_params);
        }

        /**
         * Tracking report
         */
        if (isset($_POST['report'])
            && (isset($_POST['delete_ddlog']) || isset($_POST['delete_dmlog']))
        ) {
            $html .= $this->tracking->deleteTrackingReportRows($data);
        }

        if (isset($_POST['report']) || isset($_POST['report_export'])) {
            $html .= $this->tracking->getHtmlForTrackingReport(
                $data,
                $url_params,
                $selection_schema,
                $selection_data,
                $selection_both,
                (int) $filter_ts_to,
                (int) $filter_ts_from,
                $filter_users
            );
        }

        /**
         * Main page
         */
        $html .= $this->tracking->getHtmlForMainPage(
            $url_params,
            $pmaThemeImage,
            $text_dir
        );

        $html .= '<br class="clearfloat">';

        $this->response->addHTML($html);
    }
}
