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
require_once './libraries/user_preferences.inc.php';

// handle form display and processing

$form_display = new FormDisplay();
foreach ($forms[$form_param] as $form_name => $form) {
    // skip Developer form if no setting is available
    if ($form_name == 'Developer' && !$GLOBALS['cfg']['UserprefsDeveloperTab']) {
        continue;
    }
    $form_display->registerForm($form_name, $form);
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
if (!$form_display->process(false)) {
    // handle form view and failed POST
    $form_display->display(true, true);
} else {
    // check for form errors
    if ($form_display->hasErrors()) {
        // form has errors
        ?>
        <div class="warning config-form">
            <b><?php echo __('Submitted form contains errors') ?></b>
            <?php $form_display->displayErrors(); ?>
        </div>
        <?php
        $form_display->display(true, true);
    } else {
        // save settings
        $old_settings = PMA_load_userprefs();
        $result = PMA_save_userprefs($cf->getConfigArray());
        if ($result === true) {
            PMA_userprefs_redirect($forms, $old_settings, 'prefs_forms.php', array(
                'form' => $form_param));
            exit;
        } else {
            $result->display();
        }
        $form_display->display(true, true);
    }
}

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>