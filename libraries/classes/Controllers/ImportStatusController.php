<?php
/**
 * @package PhpMyAdmin\Controllers
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;

/**
 * Import progress bar backend
 *
 * @package PhpMyAdmin\Controllers
 */
class ImportStatusController
{
    /** @var Template */
    private $template;

    /**
     * @param Template $template Template object
     */
    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function index(): void
    {
        global $SESSION_KEY, $upload_id, $plugins, $timestamp;

        list(
            $SESSION_KEY,
            $upload_id,
            $plugins
        ) = ImportAjax::uploadProgressSetup();

        // $_GET["message"] is used for asking for an import message
        if (isset($_GET['message']) && $_GET['message']) {
            // AJAX requests can't be cached!
            Core::noCacheHeader();

            header('Content-type: text/html');

            // wait 0.3 sec before we check for $_SESSION variable
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

                if (time() - $timestamp > $maximumTime) {
                    $_SESSION['Import_message']['message'] = Message::error(
                        __('Could not load the progress of the import.')
                    )->getDisplay();
                    break;
                }
            }

            echo $_SESSION['Import_message']['message'];

            echo $this->template->render('import_status', [
                'go_back_url' => $_SESSION['Import_message']['go_back_url'],
            ]);
        } else {
            ImportAjax::status($_GET['id']);
        }
    }
}
