<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Import;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Import\Ajax;
use PhpMyAdmin\Message;
use PhpMyAdmin\Routing\Route;
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
#[Route('/import-status', ['GET', 'POST'])]
class StatusController implements InvocableController
{
    /** Time to wait before checking for the $_SESSION variable. Default is 0.3 seconds. */
    private static int $sleepMicroseconds = 300000;

    /** Time to wait before rechecking for the $_SESSION variable. Default is 0.25 seconds. */
    private static int $sleepMicrosecondsRetry = 250000;

    public function __construct(private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): Response
    {
        Ajax::uploadProgressSetup();

        // $_GET["message"] is used for asking for an import message
        if ($request->hasQueryParam('message')) {
            // AJAX requests can't be cached!
            foreach (Core::getNoCacheHeaders() as $name => $value) {
                header(sprintf('%s: %s', $name, $value));
            }

            header('Content-type: text/html');

            // wait 0.3 sec before we check for $_SESSION variable
            usleep(self::$sleepMicroseconds);

            $maximumTime = ini_get('max_execution_time');
            $timestamp = time();
            // wait until message is available
            while (($_SESSION['Import_message']['message'] ?? null) == null) {
                // close session before sleeping
                session_write_close();
                // sleep
                usleep(self::$sleepMicrosecondsRetry);
                // reopen session
                session_start();

                if (time() - $timestamp > $maximumTime) {
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

        return ResponseFactory::create()->createResponse(StatusCodeInterface::STATUS_OK, 'OK');
    }
}
