<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;

use function __;
use function header;
use function ini_get;
use function session_start;
use function session_write_close;
use function sprintf;
use function time;
use function usleep;

/**
 * Import progress bar backend
 */
class StatusController
{
    public function __construct(private Template $template)
    {
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['SESSION_KEY'] ??= null;
        $GLOBALS['upload_id'] ??= null;
        $GLOBALS['plugins'] ??= null;
        $GLOBALS['timestamp'] ??= null;

        [$GLOBALS['SESSION_KEY'], $GLOBALS['upload_id'], $GLOBALS['plugins']] = Ajax::uploadProgressSetup();

        // $_GET["message"] is used for asking for an import message
        if ($request->hasQueryParam('message')) {
            // AJAX requests can't be cached!
            foreach (Core::getNoCacheHeaders() as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }

            header('Content-type: text/html');

            // wait 0.3 sec before we check for $_SESSION variable
            usleep(300000);

            $maximumTime = ini_get('max_execution_time');
            $GLOBALS['timestamp'] = time();
            // wait until message is available
            while (($_SESSION['Import_message']['message'] ?? null) == null) {
                // close session before sleeping
                session_write_close();
                // sleep
                usleep(250000); // 0.25 sec
                // reopen session
                session_start();

                if (time() - $GLOBALS['timestamp'] > $maximumTime) {
                    $_SESSION['Import_message']['message'] = Message::error(
                        __('Could not load the progress of the import.'),
                    )->getDisplay();
                    break;
                }
            }

            echo $_SESSION['Import_message']['message'] ?? '';

            if (isset($_SESSION['Import_message']['go_back_url'])) {
                echo $this->template->render('import_status', [
                    'go_back_url' => $_SESSION['Import_message']['go_back_url'],
                ]);
            }
        } else {
            Ajax::status($request->getQueryParam('id'));
        }
    }
}
