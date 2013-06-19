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

$submission_url="localhost/phpmyadminserver/reports/new";

if ($_REQUEST['send_error_report'] == true) {
    send_error_report(get_report_data());
    $response = PMA_Response::getInstance();
    $response->addJSON('message', PMA_Message::success(
        __('Thank you for submitting this report. Your page will refresh '
            .'shortly')));
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
            .'<textarea cols="80" style="height:13em; overflow:scroll">'
            .get_report_data()
            .'</textarea>';

    $html .= '<div class="label"><label><p>'
            . __('Please explain the steps that lead to the error')
            .'</p></label></div>'
            .'<textarea cols="80" style="height:10em" name="description"'
            .'id="report_description"></textarea>';

    $html .= '</fieldset>';

    $form_params = array(
        'db'    => $db,
        'table' => $table,
    );

    $html .= PMA_generate_common_hidden_inputs($form_params);
    $html .= PMA_getHiddenFields(get_report_data(false));

    $html .= '</form>';

    $response = PMA_Response::getInstance();
    $response->addHTML($html);
}

function get_report_data($json_encode = true) {
    $report = array(
        "error_message" => $_REQUEST['message'],
        "line_number" => $_REQUEST['line'],
        "file" => $_REQUEST['file'],
        "pma_version" => PMA_VERSION,
        "browser_agent" => PMA_USR_BROWSER_AGENT,
        "browser_version" => PMA_USR_BROWSER_VER,
        "operating_system" => PMA_USR_OS
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

function send_error_report($report) {
    $data_string = json_encode($report);
    $ch = curl_init($submission_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data_string))
    );

    $result = curl_exec($ch);
    return $result;
}
?>
