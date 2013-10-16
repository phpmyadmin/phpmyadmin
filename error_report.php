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

if ($_REQUEST['send_error_report'] == true) {
    PMA_sendErrorReport(PMA_getReportData(false));
    if ($_REQUEST['automatic'] === "true") {
        $response->addJSON('message', PMA_Message::error(
            __('An error has been detected and an error report has been '
                .'automatically submitted based on your settings.')
            . '<br />'
            . __('You may want to refresh the page.')));
    } else {
        $response->addJSON('message', PMA_Message::success(
            __('Thank you for submitting this report.')
            . '<br />'
            . __('You may want to refresh the page.')));
        if($_REQUEST['always_send'] === "true") {
            PMA_persistOption("SendErrorReports", "always", "ask");
        }
    }
} elseif ($_REQUEST['get_settings']) {
    $response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
} else {
    $response->addHTML(PMA_getErrorReportForm());
}

