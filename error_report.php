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

// Fail gracefully if this is not a release version of phpMyAdmin
if(!$GLOBALS['PMA_Config']->isGitRevision()) {
    $response->addJSON('message', PMA_Message::error(
        __('An error has been detected, however, you seem to be running on a git '
            . 'version of phpMyAdmin.')
            . __('Automatic report submission cannot be used. Please submit a '
            . 'manual error report on the bug tracker.')
        . '<br />'
        . __('You may want to refresh the page.')));
    $response->isSuccess(false);
    exit;
} elseif (!defined('LINE_COUNTS')) {
    $response->addJSON('message', PMA_Message::error(
        __('An error has been detected, however, the JavaScript line count file '
            . 'does not seem to exist in this phpMyAdmin installation.')
        . __('Automatic report submission cannot be used. Please submit a '
            . 'manual error report on the bug tracker.')
        . '<br />'
        . __('You may want to refresh the page.')));
    $response->isSuccess(false);
    exit;
}

if ($_REQUEST['send_error_report'] == true) {
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

