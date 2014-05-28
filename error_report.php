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
    $server_response = PMA_sendErrorReport(PMA_getReportData());

    if ($server_response === false) {
        $success = false;
    } else {
        $decoded_response = json_decode($server_response, true);
        $success = !empty($decoded_response) ? $decoded_response["success"] : false;
    }

    /* Message to show to the user */
    if ($success) {
        if (isset($_REQUEST['automatic'])
            && $_REQUEST['automatic'] === "true"
        ) {
            $message = __(
                'An error has been detected and an error report has been '
                . 'automatically submitted based on your settings.'
            );
        } else {
            $message = __('Thank you for submitting this report.');
        }
    } else {
        $message = __(
            'An error has been detected and an error report has been '
            . 'generated but failed to be sent.'
        )
        . ' '
        . __(
            'If you experience any '
            . 'problems please submit a bug report manually.'
        );
    }
    $message .= ' ' . __('You may want to refresh the page.');

    /* Create message object */
    if ($success) {
        $message = PMA_Message::notice($message);
    } else {
        $message = PMA_Message::error($message);
    }

    /* Add message to JSON response */
    $response->addJSON('message', $message);

    /* Persist always send settings */
    if (! isset($_REQUEST['automatic'])
        && $_REQUEST['automatic'] !== "true"
        && isset($_REQUEST['always_send'])
        && $_REQUEST['always_send'] === "true"
    ) {
        PMA_persistOption("SendErrorReports", "always", "ask");
    }
} elseif (! empty($_REQUEST['get_settings'])) {
    $response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
} else {
    $response->addHTML(PMA_getErrorReportForm());
}
