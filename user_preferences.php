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
require_once './libraries/config/config_functions.lib.php';
require_once './libraries/config/messages.inc.php';
require_once './libraries/config/ConfigFile.class.php';
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require './libraries/config/user_preferences.forms.php';

$GLOBALS['js_include'][] = 'config.js';

require_once './libraries/header.inc.php';

// build tabs
$tabs = array();
$form_param = filter_input(INPUT_GET, 'form');
if (!isset($forms[$form_param])) {
    $forms_keys = array_keys($forms);
    $form_param = array_shift($forms_keys);
}
foreach (array_keys($forms) as $formset) {
    $tabs[] = array(
        'link' => 'user_preferences.php',
        'text' => PMA_ifSetOr($GLOBALS['strSetupForm_' . $formset], $formset), // TODO: remove ifSetOr
        'active' => $formset == $form_param,
        'url_params' => array('form' => $formset)
    );
}

echo PMA_generate_html_tabs($tabs, array());

// handle form display and processing
$forms_all_keys = array();
foreach ($forms as $formset) {
    foreach ($formset as $form) {
        $forms_all_keys = array_merge($forms_all_keys, $form);
    }
}

$cf = ConfigFile::getInstance();
$cf->setAllowedKeys($forms_all_keys);
$cf->updateWithGlobalConfig($GLOBALS['PMA_Config']);

// todo: debug - remove
$arr = $cf->getConfigArray();
$arr2 = array();
foreach ($arr as $k => $v) {
    $arr2[] = "<b>$k</b> " . var_export($v, true);
}
$arr2 = implode(', ', $arr2);
$msg = !empty($arr2) ? PMA_Message::notice('Debug: ' . $arr2) : PMA_Message::notice('no settings');
$msg->display();

$form_display = new FormDisplay();
foreach ($forms[$form_param] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
    // redirect
    $url_params = array('form' => $form_param);
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'user_preferences.php'
            . PMA_generate_common_url($url_params, '&'));
    exit;
}
if (!$form_display->process(false)) {
    // handle form view and failed POST
    $form_display->display(true, true);
} else {
    // check for form errors
    if ($form_display->hasErrors()) {
        // form has errors
        ?>
        <fieldset>
            <b><?php echo __('Submitted form contains errors') ?></b>
            <?php $form_display->displayErrors(); ?>
        </fieldset>
        <?php
        $form_display->display(true, true);
    } else {
        // save settings
        $result = PMA_save_userprefs();
        if ($result === true) {
            $message = PMA_Message::rawSuccess(__('Configuration has been saved'));
            $message->display();
            // redirect
            //$url_params = array('form' => $form_param);
            //PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'user_preferences.php'
            //        . PMA_generate_common_url($url_params, '&'));
            //exit;
        } else {
            $result->display();
        }
        $form_display->display(true, true);
    }
}
$GLOBALS['error_handler']->dispAllErrors();

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>