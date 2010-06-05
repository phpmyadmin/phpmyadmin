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

$GLOBALS['js_include'][] = 'js/settings_forms.js';

require_once './libraries/header.inc.php';

// Any message to display?
if (! empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

$common_url_query =  PMA_generate_common_url('', '');

$tabs = array();
foreach (array_keys($forms) as $form) {
    $tabs[] = array(
        'link' => 'user_preferences.php',
        'text' => PMA_ifSetOr($GLOBALS['strSetupForm_' . $form], $form), // TODO: remove ifSetOr
        'active' => $form == PMA_ifSetOr($_GET['form'], ''),
        'url_params' => array('form' => $form)
    );
}

echo PMA_generate_html_tabs($tabs, array());


/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>