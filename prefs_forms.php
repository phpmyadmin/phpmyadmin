<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';

$userPreferences = new UserPreferences();

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
$userPreferences->pageInit($cf);

// handle form processing

$form_param = isset($_GET['form']) ? $_GET['form'] : null;
$form_class = UserFormList::get($form_param);
if (is_null($form_class)) {
    Core::fatalError(__('Incorrect form specified!'));
}

$form_display = new $form_class($cf, 1);

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
    // redirect
    $url_params = array('form' => $form_param);
    Core::sendHeaderLocation(
        './prefs_forms.php'
        . Url::getCommonRaw($url_params)
    );
    exit;
}

$error = null;
if ($form_display->process(false) && !$form_display->hasErrors()) {
    // save settings
    $result = $userPreferences->save($cf->getConfigArray());
    if ($result === true) {
        // reload config
        $GLOBALS['PMA_Config']->loadUserPreferences();
        $tabHash = isset($_POST['tab_hash']) ? $_POST['tab_hash'] : null;
        $hash = ltrim($tabHash, '#');
        $userPreferences->redirect(
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
