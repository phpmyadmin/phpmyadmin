<?php
/**
 * Handle error report submission
 */

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\ErrorReport;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\UserPreferences;

use function __;
use function in_array;
use function is_string;
use function json_decode;
use function time;

/**
 * Handle error report submission
 */
class ErrorReportController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private ErrorReport $errorReport,
        private ErrorHandler $errorHandler,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        /** @var string $exceptionType */
        $exceptionType = $request->getParsedBodyParam('exception_type', '');
        /** @var string|null $automatic */
        $automatic = $request->getParsedBodyParam('automatic');
        /** @var string|null $alwaysSend */
        $alwaysSend = $request->getParsedBodyParam('always_send');

        if (! in_array($exceptionType, ['js', 'php'])) {
            return;
        }

        if ($request->hasBodyParam('send_error_report')) {
            if ($exceptionType === 'php') {
                /**
                 * Prevent infinite error submission.
                 * Happens in case error submissions fails.
                 * If reporting is done in some time interval,
                 *  just clear them & clear json data too.
                 */
                if (
                    isset($_SESSION['prev_error_subm_time'], $_SESSION['error_subm_count'])
                    && $_SESSION['error_subm_count'] >= 3
                    && ($_SESSION['prev_error_subm_time'] - time()) <= 3000
                ) {
                    $_SESSION['error_subm_count'] = 0;
                    $_SESSION['prev_errors'] = '';
                    $this->response->addJSON('stopErrorReportLoop', '1');
                } else {
                    $_SESSION['prev_error_subm_time'] = time();
                    $_SESSION['error_subm_count'] = isset($_SESSION['error_subm_count'])
                        ? $_SESSION['error_subm_count'] + 1
                        : 0;
                }
            }

            $reportData = $this->errorReport->getData($exceptionType);
            // report if and only if there were 'actual' errors.
            if ($reportData !== []) {
                $serverResponse = $this->errorReport->send($reportData);
                if (! is_string($serverResponse)) {
                    $success = false;
                } else {
                    $decodedResponse = json_decode($serverResponse, true);
                    $success = ! empty($decodedResponse) && $decodedResponse['success'];
                }

                /* Message to show to the user */
                if ($success) {
                    if ($automatic === 'true' || $GLOBALS['cfg']['SendErrorReports'] === 'always') {
                        $msg = __(
                            'An error has been detected and an error report has been '
                            . 'automatically submitted based on your settings.',
                        );
                    } else {
                        $msg = __('Thank you for submitting this report.');
                    }
                } else {
                    $msg = __(
                        'An error has been detected and an error report has been generated but failed to be sent.',
                    );
                    $msg .= ' ';
                    $msg .= __('If you experience any problems please submit a bug report manually.');
                }

                $msg .= ' ' . __('You may want to refresh the page.');

                /* Create message object */
                if ($success) {
                    $msg = Message::notice($msg);
                } else {
                    $msg = Message::error($msg);
                }

                /* Add message to response */
                if ($this->response->isAjax()) {
                    if ($exceptionType === 'js') {
                        $this->response->addJSON('message', $msg);
                    } else {
                        $this->response->addJSON('errSubmitMsg', $msg);
                    }
                } elseif ($exceptionType === 'php') {
                    $jsCode = 'window.ajaxShowMessage(\'<div class="alert alert-danger" role="alert">'
                        . $msg
                        . '</div>\', false);';
                    $this->response->getFooterScripts()->addCode($jsCode);
                }

                if ($exceptionType === 'php') {
                    // clear previous errors & save new ones.
                    $this->errorHandler->savePreviousErrors();
                }

                /* Persist always send settings */
                if ($alwaysSend === 'true') {
                    $userPreferences = new UserPreferences($this->dbi);
                    $userPreferences->persistOption('SendErrorReports', 'always', 'ask');
                }
            }
        } elseif ($request->hasBodyParam('get_settings')) {
            $this->response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
        } elseif ($exceptionType === 'js') {
            $this->response->addJSON('report_modal', $this->errorReport->getEmptyModal());
            $this->response->addHTML($this->errorReport->getForm());
        } else {
            // clear previous errors & save new ones.
            $this->errorHandler->savePreviousErrors();
        }
    }
}
