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
require_once './libraries/common.inc.php';
require_once './libraries/user_preferences.lib.php';
require_once './libraries/config/config_functions.lib.php';
require_once './libraries/config/messages.inc.php';
require_once './libraries/config/ConfigFile.class.php';
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require './libraries/config/user_preferences.forms.php';

PMA_userprefs_pageinit();

// handle form processing

$form_param = filter_input(INPUT_GET, 'form');
if (! isset($forms[$form_param])) {
    $forms_keys = array_keys($forms);
    $form_param = array_shift($forms_keys);
}

$form_display = new FormDisplay();
foreach ($forms[$form_param] as $form_name => $form) {
    // skip Developer form if no setting is available
    if ($form_name == 'Developer' && !$GLOBALS['cfg']['UserprefsDeveloperTab']) {
        continue;
    }
    $form_display->registerForm($form_name, $form, 1);
}

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
    // redirect
    $url_params = array('form' => $form_param);
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'prefs_forms.php'
            . PMA_generate_common_url($url_params, '&'));
    exit;
}

$error = null;
if ($form_display->process(false) && !$form_display->hasErrors()) {
    // save settings
    $old_settings = PMA_load_userprefs();
    $result = PMA_save_userprefs(ConfigFile::getInstance()->getConfigArray());
    if ($result === true) {
        // reload config
        $GLOBALS['PMA_Config']->loadUserPreferences();
        $hash = ltrim(filter_input(INPUT_POST, 'tab_hash'), '#');
        PMA_userprefs_redirect($forms, $old_settings, 'prefs_forms.php', array(
            'form' => $form_param), $hash);
        exit;
    } else {
        $error = $result;
    }
}

// display forms
$GLOBALS['js_include'][] = 'config.js';
require './libraries/header.inc.php';
require './libraries/user_preferences.inc.php';
if ($error) {
    $error->display();
}
if ($form_display->hasErrors()) {
    // form has errors
    ?>
    <div class="error config-form">
        <b><?php echo __('Cannot save settings, submitted form contains errors') ?></b>
        <?php $form_display->displayErrors(); ?>
    </div>
    <?php
}
$form_display->display(true, true);

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
