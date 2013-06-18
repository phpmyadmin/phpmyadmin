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

if ($_REQUEST['send_error_report'] == true) {

} else {
    $html = "";
    $html .= '<form action="error_report.php" method="post" name="report_frm"'
            .' id="report_frm" class="ajax">'
            .'<fieldset style="padding-top:0px">';

    $html .= '<p>Phpmyadmin has encountered an error. We have collected data about'
            .' this error as well as information about relevant configuration'
            .' settings to send to phpmyadmin for processing to help us in'
            .' debugging the problem</p>';

    $html .= '<div class="label">'
            .'<label><p>You may examine the data in the error report:</p></label>'
            .'</div>'
            .'<textarea cols="80" style="height:13em; overflow:scroll">'
            .get_error_report()
            .'</textarea>';

    $html .= '<div class="label">'
            .'<label><p>Please enter any extra info that may help us in the diagnosing '
            .'of this problem</p></label></div>'
            .'<textarea cols="80" style="height:10em">'
            .''
            .'</textarea>';

    $html .= '</fieldset>';

    $form_params = array(
        'db'    => $db,
        'table' => $table,
    );

    $html .= PMA_generate_common_hidden_inputs($form_params);

    $html .= '</form>';

    $response = PMA_Response::getInstance();
    $response->addHTML($html);
}

function get_error_report() {
    $report = array(
        "error_message" => $_REQUEST['message'],
        "line_number" => $_REQUEST['line'],
        "file" => $_REQUEST['file'],
    );
    return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
?>
