<?php
/**
 * Import progress bar backend
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Core;
use PhpMyAdmin\Display\ImportAjax;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

define('PMA_MINIMUM_COMMON', 1);

require_once ROOT_PATH . 'libraries/common.inc.php';
list(
    $SESSION_KEY,
    $upload_id,
    $plugins
) = ImportAjax::uploadProgressSetup();

global $containerBuilder;

// $_GET["message"] is used for asking for an import message
if (isset($_GET['message']) && $_GET['message']) {
    // AJAX requests can't be cached!
    Core::noCacheHeader();

    header('Content-type: text/html');

    // wait 0.3 sec before we check for $_SESSION variable,
    // which is set inside libraries/entry_points/import.php
    usleep(300000);

    $maximumTime = ini_get('max_execution_time');
    $timestamp = time();
    // wait until message is available
    while ($_SESSION['Import_message']['message'] == null) {
        // close session before sleeping
        session_write_close();
        // sleep
        usleep(250000); // 0.25 sec
        // reopen session
        session_start();

        if ((time() - $timestamp) > $maximumTime) {
            $_SESSION['Import_message']['message'] = PhpMyAdmin\Message::error(
                __('Could not load the progress of the import.')
            )->getDisplay();
            break;
        }
    }

    echo $_SESSION['Import_message']['message'];

    $template = $containerBuilder->get('template');

    echo $template->render('import_status', [
        'go_back_url' => $_SESSION['Import_message']['go_back_url'],
    ]);
} else {
    ImportAjax::status($_GET['id']);
}
