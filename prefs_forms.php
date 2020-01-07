<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPreferences;
use PhpMyAdmin\UserPreferencesHeader;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

/**
 * Gets some core libraries and displays a top message if required
 */
require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Template $template */
$template = $containerBuilder->get('template');
$userPreferences = new UserPreferences();

$cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
$userPreferences->pageInit($cf);

// handle form processing

$form_param = isset($_GET['form']) ? $_GET['form'] : null;
$form_class = UserFormList::get($form_param);
if ($form_class === null) {
    Core::fatalError(__('Incorrect form specified!'));
}

/** @var BaseForm $form_display */
$form_display = new $form_class($cf, 1);

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
    // redirect
    $url_params = ['form' => $form_param];
    Core::sendHeaderLocation(
        './prefs_forms.php'
        . Url::getCommonRaw($url_params)
    );
    exit;
}

$error = null;
if ($form_display->process(false) && ! $form_display->hasErrors()) {
    // Load 2FA settings
    $twoFactor = new TwoFactor($GLOBALS['cfg']['Server']['user']);
    // save settings
    $result = $userPreferences->save($cf->getConfigArray());
    // save back the 2FA setting only
    $twoFactor->save();
    if ($result === true) {
        // reload config
        $GLOBALS['PMA_Config']->loadUserPreferences();
        $tabHash = isset($_POST['tab_hash']) ? $_POST['tab_hash'] : null;
        $hash = ltrim($tabHash, '#');
        $userPreferences->redirect(
            'prefs_forms.php',
            ['form' => $form_param],
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

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
echo UserPreferencesHeader::getContent($template, $relation);

if ($form_display->hasErrors()) {
    $formErrors = $form_display->displayErrors();
}

echo $template->render('preferences/forms/main', [
    'error' => $error ? $error->getDisplay() : '',
    'has_errors' => $form_display->hasErrors(),
    'errors' => $formErrors ?? null,
    'form' => $form_display->getDisplay(true, true, true, 'prefs_forms.php?form=' . $form_param, [
        'server' => $GLOBALS['server'],
    ]),
]);

if ($response->isAjax()) {
    $response->addJSON('disableNaviSettings', true);
} else {
    define('PMA_DISABLE_NAVI_SETTINGS', true);
}
