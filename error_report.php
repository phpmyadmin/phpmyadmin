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

if (isset($_REQUEST['exception_type']) 
    && $_REQUEST['exception_type'] == 'js'
    ) {
    if (isset($_REQUEST['send_error_report'])
        && $_REQUEST['send_error_report'] == true
    ) {
        $server_response = PMA_sendErrorReport(PMA_getReportData(false,'js'));

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
                            . 'automatically submitted based on your settings.'
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
}elseif (isset($_REQUEST['exception_type']) 
        && $_REQUEST['exception_type'] == 'php'
        ) {
        if (isset($_REQUEST['send_error_report'])
            && $_REQUEST['send_error_report'] == '1'
            ) {
            $reportData = PMA_getReportData(false,'php');
            $server_response = PMA_sendErrorReport($reportData);
            if ($server_response === false) {
                $success = false;
            } else {
                $decoded_response = json_decode($server_response, true);
                $success = !empty($decoded_response) ? $decoded_response["success"] : false;
            }
            
            if($GLOBALS['cfg']['SendErrorReports'] == 'ask'){
                if($success) {
                    $errSubmitMsg = PMA_Message::error(
                                __('Thank You for subitting error report!!')
                                . '<br/>'
                                . __('Report has been succesfully submitted.')
                            );
                } else {
                    $errSubmitMsg = PMA_Message::error(
                                __('Thank You for subitting error report!!')
                                . '<br/>'
                                . __(' Unfortunately submission failed.')
                                . '<br/>'
                                . __(' If you experience any problems please submit a bug report manually.')
                            );
                }
            } elseif($GLOBALS['cfg']['SendErrorReports'] == 'always') {
                if($success) {
                    $errSubmitMsg = PMA_Message::error(
                                __(
                                    'An error has been detected on the server and an error report has been '
                                    . 'automatically submitted based on your settings.'
                                )
                            );
                } else {
                    $errSubmitMsg = PMA_Message::error(
                                __(
                                    'An error has been detected and an error report has been '
                                    . 'generated but failed to be sent.'
                                )
                                . '<br/>'
                                . __('If you experience any problems please submit a bug report manually.')
                            );
                }
            }

            if($response->isAjax()) {
                $response->addJSON('_errSubmitMsg',$errSubmitMsg);
            } else {
                $jsCode = 'PMA_ajaxShowMessage("<div class=\"error\">'
                        .$errSubmitMsg
                        .'</div>", false);';
                $response->getFooter()->getScripts()->addCode($jsCode);
            }
        }
        // clear previous errors & save new ones.
        $GLOBALS['error_handler']->savePreviousErrors();
} else {
    die('Oops, something went wrong!!');
}

require_once 'libraries/common_error_reporting.inc.php';
