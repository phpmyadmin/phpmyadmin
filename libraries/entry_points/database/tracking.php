<?php
/**
 * Tracking configuration for database
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tracker;
use PhpMyAdmin\Tracking;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

if (! defined('PHPMYADMIN')) {
    exit;
}

global $containerBuilder, $db, $pmaThemeImage, $text_dir, $url_query, $url_params, $tables, $num_tables, $pos, $data;
global $total_num_tables, $sub_part, $is_show_stats, $db_is_system_schema, $tooltip_truename, $tooltip_aliasname;

//Get some js files needed for Ajax requests
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('vendor/jquery/jquery.tablesorter.js');
$scripts->addFile('database/tracking.js');

/** @var Tracking $tracking */
$tracking = $containerBuilder->get('tracking');

/** @var Template $template */
$template = $containerBuilder->get('template');

/**
 * If we are not in an Ajax request, then do the common work and show the links etc.
 */
require ROOT_PATH . 'libraries/db_common.inc.php';
$url_params['goto'] = Url::getFromRoute('/table/tracking');
$url_params['back'] = Url::getFromRoute('/database/tracking');
$url_query .= Url::getCommon($url_params, '&');

// Get the database structure
$sub_part = '_structure';

[
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos,
] = Util::getDbInfo($db, $sub_part === null ? '' : $sub_part);

if (isset($_POST['delete_tracking'], $_POST['table'])) {
    Tracker::deleteTracking($db, $_POST['table']);
    Message::success(
        __('Tracking data deleted successfully.')
    )->display();
} elseif (isset($_POST['submit_create_version'])) {
    $tracking->createTrackingForMultipleTables($_POST['selected']);
    Message::success(
        sprintf(
            __(
                'Version %1$s was created for selected tables,'
                . ' tracking is active for them.'
            ),
            htmlspecialchars($_POST['version'])
        )
    )->display();
} elseif (isset($_POST['submit_mult'])) {
    if (! empty($_POST['selected_tbl'])) {
        if ($_POST['submit_mult'] == 'delete_tracking') {
            foreach ($_POST['selected_tbl'] as $table) {
                Tracker::deleteTracking($db, $table);
            }
            Message::success(
                __('Tracking data deleted successfully.')
            )->display();
        } elseif ($_POST['submit_mult'] == 'track') {
            echo $template->render('create_tracking_version', [
                'route' => '/database/tracking',
                'url_params' => $url_params,
                'last_version' => 0,
                'db' => $db,
                'selected' => $_POST['selected_tbl'],
                'type' => 'both',
                'default_statements' => $GLOBALS['cfg']['Server']['tracking_default_statements'],
            ]);
            exit;
        }
    } else {
        Message::notice(
            __('No tables selected.')
        )->display();
    }
}

// Get tracked data about the database
$data = Tracker::getTrackedData($db, '', '1');

// No tables present and no log exist
if ($num_tables == 0 && count($data['ddlog']) === 0) {
    echo '<p>' , __('No tables found in database.') , '</p>' , "\n";

    if (empty($db_is_system_schema)) {
        echo CreateTable::getHtml($db);
    }
    exit;
}

// ---------------------------------------------------------------------------
echo $tracking->getHtmlForDbTrackingTables(
    $db,
    $url_params,
    $pmaThemeImage,
    $text_dir
);

// If available print out database log
if (count($data['ddlog']) > 0) {
    $log = '';
    foreach ($data['ddlog'] as $entry) {
        $log .= '# ' . $entry['date'] . ' ' . $entry['username'] . "\n"
            . $entry['statement'] . "\n";
    }
    echo Generator::getMessage(__('Database Log'), $log);
}
