<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';
require_once './libraries/user_preferences.lib.php';
require_once './libraries/config/messages.inc.php';
require_once './libraries/config/FormDisplay.class.php';

$GLOBALS['js_include'][] = 'js/settings_forms.js';

require_once './libraries/header.inc.php';

// Any message to display?
if (! empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

$common_url_query =  PMA_generate_common_url('', '');

$tabs = array();
$active_formset = filter_input(INPUT_GET, 'form');
if (!isset($forms[$active_formset])) {
    $active_formset = array_shift(array_keys($forms));
}
foreach (array_keys($forms) as $form) {
    $tabs[] = array(
        'link' => 'user_preferences.php',
        'text' => PMA_ifSetOr($GLOBALS['strSetupForm_' . $form], $form), // TODO: remove ifSetOr
        'active' => $form == $active_formset,
        'url_params' => array('form' => $form)
    );
}

echo PMA_generate_html_tabs($tabs, array());

$form_display = new FormDisplay();
foreach ($forms[$active_formset] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>