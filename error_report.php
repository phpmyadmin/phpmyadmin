<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Display the form to edit/create an index
 *
 * @package PhpMyAdmin
 */
/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/tbl_info.inc.php';
require_once 'libraries/user_preferences.lib.php';

$submission_url="localhost/phpmyadminserver/reports/new";

$response = PMA_Response::getInstance();

if ($_REQUEST['send_error_report'] == true) {
    send_error_report(get_report_data(false));
    if ($_REQUEST['automatic'] == true) {
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
        if($_REQUEST['always_send'] == true) {
            PMA_persistOption("SendErrorReports", "always", "ask");
        }
    }
} elseif ($_REQUEST['get_settings']) {
    $response->addJSON('report_setting', $GLOBALS['cfg']['SendErrorReports']);
} else {
    $html = "";
    $html .= '<form action="error_report.php" method="post" name="report_frm"'
            .' id="report_frm" class="ajax">'
            .'<fieldset style="padding-top:0px">';

    $html .= '<p>'
            . __('Phpmyadmin has encountered an error. We have collected data about'
            .' this error as well as information about relevant configuration'
            .' settings to send to phpmyadmin for processing to help us in'
            .' debugging the problem')
            .'</p>';

    $html .= '<div class="label"><label><p>'
            . __('You may examine the data in the error report:')
            .'</p></label></div>'
            .'<textarea cols="80" style="height:13em; overflow:scroll" disabled>'
            .get_report_data()
            .'</textarea>';

    $html .= '<div class="label"><label><p>'
            . __('Please explain the steps that lead to the error:')
            .'</p></label></div>'
            .'<textarea cols="80" style="height:10em" name="description"'
            .'id="report_description"></textarea>';

    $html .= '<input type="checkbox" name="always_send"'
            .' id="always_send_checkbox"/>'
            .'<span>'
            . __('Automatically send report next time')
            .'</span>';

    $html .= '</fieldset>';

    $form_params = array(
        'db'    => $db,
        'table' => $table,
    );

    $html .= PMA_generate_common_hidden_inputs($form_params);
    $html .= PMA_getHiddenFields(get_report_data(false));

    $html .= '</form>';

    $response->addHTML($html);
}

/**
 * returns the error report data collected from the current configuration or
 * from the request parameters sent by the error reporting js code.
 *
 * @param boolean $json_encode whether to encode the array as a json string
 *
 * @return Array/String $report
 */
function get_report_data($json_encode = true) {
    $report = array(
        "error_message" => $_REQUEST['message'],
        "line_number" => $_REQUEST['line'],
        "file" => $_REQUEST['file'],
        "pma_version" => PMA_VERSION,
        "browser_agent" => PMA_USR_BROWSER_AGENT,
        "browser_version" => PMA_USR_BROWSER_VER,
        "operating_system" => PMA_USR_OS,
        "user_agent_string" => $_SERVER['HTTP_USER_AGENT'],
        "current_locale" => $_COOKIE['pma_lang'],
        "current_url" => $_REQUEST['current_url'],
        "configuration_storage_enabled" =>
            !empty($GLOBALS['cfg']['Servers'][1]['pmadb']),
        "php_version" => phpversion(),
        "microhistory" => $_REQUEST['microhistory'],
    );

    if(!empty($_REQUEST['description'])) {
        $report['description'] = $_REQUEST['description'];
    }

    if($json_encode) {
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        return $report;
    }
}

/**
 * Sends report data to the error reporting server
 *
 * @param Array $report the report info to be sent
 *
 * @return String $result the reply of the server
 */
function send_error_report($report) {
    $data_string = json_encode($report);
    if (ini_get('allow_url_fopen')) {
        if (strlen($cfg['VersionCheckProxyUrl'])) {
            $context = array(
                'http' => array(
                    'proxy' => $cfg['VersionCheckProxyUrl'],
                    'request_fulluri' => true
                )
            );
            if (strlen($cfg['VersionCheckProxyUser'])) {
                $auth = base64_encode(
                    $cfg['VersionCheckProxyUser'] . ':' . $cfg['VersionCheckProxyPass']
                );
                $context['http']['header'] = 'Proxy-Authorization: Basic ' . $auth;
            }
            $response = file_get_contents(
                $submission_url,
                false,
                stream_context_create($context)
            );
        } else {
            $response = file_get_contents($file);
        }
    } else if (function_exists('curl_init')) {
        $curl_handle = curl_init($submission_url);
        if (strlen($cfg['VersionCheckProxyUrl'])) {
            curl_setopt($curl_handle, CURLOPT_PROXY, $cfg['VersionCheckProxyUrl']);
            if (strlen($cfg['VersionCheckProxyUser'])) {
                curl_setopt(
                    $curl_handle,
                    CURLOPT_PROXYUSERPWD,
                    $cfg['VersionCheckProxyUser'] . ':' . $cfg['VersionCheckProxyPass']
                );
            }
        }
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($curl_handle);
    }
    return $response;
}
?>
