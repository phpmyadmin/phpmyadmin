<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\config\ConfigFile;
use PMA\libraries\config\FormDisplay;
use PMA\libraries\Response;
use PMA\libraries\URL;

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require 'libraries/config/user_preferences.forms.php';

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
PMA_userprefsPageInit($cf);

// handle form processing

$form_param = isset($_GET['form']) ? $_GET['form'] : null;
if (! isset($forms[$form_param])) {
    $forms_keys = array_keys($forms);
    $form_param = array_shift($forms_keys);
}

$form_display = new FormDisplay($cf);
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
    PMA_sendHeaderLocation(
        './prefs_forms.php'
        . URL::getCommonRaw($url_params)
    );
    exit;
}

$error = null;
if ($form_display->process(false) && !$form_display->hasErrors()) {
    // save settings
    $result = PMA_saveUserprefs($cf->getConfigArray());
    if ($result === true) {
        // reload config
        $GLOBALS['PMA_Config']->loadUserPreferences();
        $tabHash = isset($_POST['tab_hash']) ? $_POST['tab_hash'] : null;
        $hash = ltrim($tabHash, '#');
        PMA_userprefsRedirect(
            'prefs_forms.php',
            array('form' => $form_param),
            $hash
        );
        exit;
    } else {
        $error = $result;
    }
}

// display forms
$response = Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('config.js');

require 'libraries/user_preferences.inc.php';
if ($error) {
    $error->display();
}
if ($form_display->hasErrors()) {
    // form has errors
    ?>
    <div class="error config-form">
        <b>
            <?php echo __('Cannot save settings, submitted form contains errors!') ?>
        </b>
        <?php echo $form_display->displayErrors(); ?>
    </div>
    <?php
}
echo $form_display->getDisplay(true, true);

if ($response->isAjax()) {
    $response->addJSON('_disableNaviSettings', true);
} else {
    define('PMA_DISABLE_NAVI_SETTINGS', true);
}
