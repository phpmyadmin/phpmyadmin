<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handle error report submission
 *
 * @package PhpMyAdmin
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/error_report.lib.php';
require_once 'libraries/user_preferences.lib.php';

if (!isset($_REQUEST['exception_type'])
    ||!in_array($_REQUEST['exception_type'], array('js', 'php'))
) {
    die('Oops, something went wrong!!');
}

$response = PMA_Response::getInstance();

if (isset($_REQUEST['send_error_report'])
    && ($_REQUEST['send_error_report'] == true
    || $_REQUEST['send_error_report'] == '1')
) {
    if ($_REQUEST['exception_type'] == 'php') {
        /**
         * Prevent infinite error submission.
         * Happens in case error submissions fails.
         * If reporting is done in some time interval,
         *  just clear them & clear json data too.
         */
        if (isset($_SESSION['prev_error_subm_time'])
            && isset($_SESSION['error_subm_count'])
            && $_SESSION['error_subm_count'] >= 3
            && ($_SESSION['prev_error_subm_time']-time()) <= 3000
        ) {
            $_SESSION['error_subm_count'] = 0;
            $_SESSION['prev_errors'] = '';
             $response = PMA_Response::getInstance();
            $response->addJSON('_stopErrorReportLoop', '1');
        } else {
            $_SESSION['prev_error_subm_time'] = time();
            $_SESSION['error_subm_count'] = (
                (isset($_SESSION['error_subm_count']))
                    ? ($_SESSION['error_subm_count']+1)
                    : (0)
            );
        }
    }
    $reportData = PMA_getReportData($_REQUEST['exception_type']);
    // report if and only if there were 'actual' errors.
    if (count($reportData) > 0) {
        $server_response = PMA_sendErrorReport($reportData);
        if ($server_response === false) {
            $success = false;
        } else {
            $decoded_response = json_decode($server_response, true);
            $success = !empty($decoded_response) ?
                $decoded_response["success"] : false;
        }

        /* Message to show to the user */
        if ($success) {
            if ((isset($_REQUEST['automatic'])
                && $_REQUEST['automatic'] === "true")
                || $GLOBALS['cfg']['SendErrorReports'] == 'always'
            ) {
                $msg = __(
                    'An error has been detected and an error report has been '
                    . 'automatically submitted based on your settings.'
                );
            } else {
                $msg = __('Thank you for submitting this report.');
            }
        } else {
            $msg = __(
                'An error has been detected and an error report has been '
                . 'generated but failed to be sent.'
            )
            . ' '
            . __(
                'If you experience any '
                . 'problems please submit a bug report manually.'
            );
        }
        $msg .= ' ' . __('You may want to refresh the page.');

        /* Create message object */
        if ($success) {
            $msg = PMA_Message::notice($msg);
        } else {
            $msg = PMA_Message::error($msg);
        }

        /* Add message to response */
        if ($response->isAjax()) {
            if ($_REQUEST['exception_type'] == 'js') {
                $response->addJSON('message', $msg);
            } else {
                $response->addJSON('_errSubmitMsg', $msg);
            }
        } elseif ($_REQUEST['exception_type'] == 'php') {
            $jsCode = 'PMA_ajaxShowMessage("<div class=\"error\">'
                    . $msg
                    . '</div>", false);';
            $response->getFooter()->getScripts()->addCode($jsCode);
        }

        if ($_REQUEST['exception_type'] == 'php') {
            // clear previous errors & save new ones.
            $GLOBALS['error_handler']->savePreviousErrors();
        }

        /* Persist always send settings */
        if (isset($_REQUEST['always_send'])
            && $_REQUEST['always_send'] === "true"
        ) {
            PMA_persistOption("SendErrorReports", "always", "ask");
        }
    }
} elseif (! empty($_REQUEST['get_settings'])) {
    $response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
} else {
    if ($_REQUEST['exception_type'] == 'js') {
        $response->addHTML(PMA_getErrorReportForm());
    } else {
        // clear previous errors & save new ones.
        $GLOBALS['error_handler']->savePreviousErrors();
    }
}
