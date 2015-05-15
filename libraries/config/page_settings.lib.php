<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/config/FormDisplay.class.php';
require 'libraries/config/user_preferences.forms.php';

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
PMA_userprefsPageInit($cf);

$form_display = new FormDisplay($cf);
foreach ($forms['Sql_queries'] as $form_name => $form) {
    // skip Developer form if no setting is available
    if ($form_name == 'Developer' && !$GLOBALS['cfg']['UserprefsDeveloperTab']) {
        continue;
    }
    $form_display->registerForm($form_name, $form, 1);
}

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
}

$error = null;
if ($form_display->process(false) && !$form_display->hasErrors()) {
    // save settings
    $result = PMA_saveUserprefs($cf->getConfigArray());
    if ($result === true) {
        // reload config
        $GLOBALS['PMA_Config']->loadUserPreferences();
        $hash = ltrim(filter_input(INPUT_POST, 'tab_hash'), '#');
        header('Location: '.$_SERVER['REQUEST_URI']);
        exit();
    } else {
        $error = $result;
    }
}

// display forms
$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('config.js');
$scripts->addFile('page_settings.js');

$response->addHTML('<div class="page_settings_modal">');

if ($error) {
    $error->getDisplay();
}
if ($form_display->hasErrors()) {
    // form has errors
    $response->addHTML(
        '<div class="error config-form">'
        . '<b>' . __('Cannot save settings, submitted form contains errors!') . '</b>'
        . $form_display->displayErrors()
        . '</div>'
    );
}
$response->addHTML($form_display->getDisplay(
    true,
    true,
    false,
    $response->getFooter()->getSelfUrl('unencoded'),
    array(
        'submit_save' => 'Submit'
    )
));
$response->addHTML('</div>');
