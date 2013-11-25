<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handle error report submission
 *
 * @package PhpMyAdmin
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/error_report.lib.php';

$response = PMA_Response::getInstance();

if (isset($_REQUEST['send_error_report'])
    && $_REQUEST['send_error_report'] == true
) {
    $server_response = PMA_sendErrorReport(PMA_getReportData(false));

    if ($server_response === false) {
        $success = false;
    } else {
        $decoded_response = json_decode($server_response, true);
        $success = !empty($decoded_response) ? $decoded_response["success"] : false;
    }

    if (isset($_REQUEST['automatic'])
        && $_REQUEST['automatic'] === "true"
    ) {
        if ($success) {
            $response->addJSON(
                'message',
                PMA_Message::error(
                    __(
                        'An error has been detected and an error report has been '
                        .'automatically submitted based on your settings.'
                    )
                    . '<br />'
                    . __('You may want to refresh the page.')
                )
            );
        } else {
            $response->addJSON(
                'message',
                PMA_Message::error(
                    __(
                        'An error has been detected and an error report has been '
                        . 'generated but failed to be sent.'
                    )
                    . ' '
                    . __(
                        'If you experience any '
                        . 'problems please submit a bug report manually.'
                    )
                    . '<br />'
                    . __('You may want to refresh the page.')
                )
            );
        }
    } else {
        if ($success) {
            $response->addJSON(
                'message',
                PMA_Message::success(
                    __('Thank you for submitting this report.')
                    . '<br />'
                    . __('You may want to refresh the page.')
                )
            );
        } else {
            $response->addJSON(
                'message',
                PMA_Message::error(
                    __('Thank you for submitting this report.')
                    . ' '
                    . __('Unfortunately the submission failed.')
                    . ' '
                    . __(
                        'If you experience any '
                        . 'problems please submit a bug report manually.'
                    )
                    . '<br />'
                    . __('You may want to refresh the page.')
                )
            );
        }
        if (isset($_REQUEST['always_send'])
            && $_REQUEST['always_send'] === "true"
        ) {
            PMA_persistOption("SendErrorReports", "always", "ask");
        }
    }
} elseif (! empty($_REQUEST['get_settings'])) {
    $response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
} else {
    $response->addHTML(PMA_getErrorReportForm());
}
