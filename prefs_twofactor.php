<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Message;
use PhpMyAdmin\TwoFactor;
use PhpMyAdmin\Template;

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';

require 'libraries/user_preferences.inc.php';

$two_factor = new TwoFactor($GLOBALS['cfg']['Server']['user']);

if (isset($_POST['2fa_remove'])) {
    if (! $two_factor->check(true)) {
        echo Template::get('prefs_twofactor_confirm')->render([
            'form' => $two_factor->render(),
        ]);
        exit;
    } else {
        $two_factor->configure('');
        Message::rawNotice(__('Two-factor authentication has been removed.'))->display();
    }
} elseif (isset($_POST['2fa_configure'])) {
    if (! $two_factor->configure($_POST['2fa_configure'])) {
        echo Template::get('prefs_twofactor_configure')->render([
            'form' => $two_factor->setup(),
            'configure' => $_POST['2fa_configure'],
        ]);
        exit;
    } else {
        Message::rawNotice(__('Two-factor authentication has been configured.'))->display();
    }
}

$backend = $two_factor->backend;
echo Template::get('prefs_twofactor')->render([
    'enabled' => $two_factor->writable,
    'num_backends' => count($two_factor->available),
    'backend_id' => $backend::$id,
    'backend_name' => $backend::getName(),
    'backend_description' => $backend::getDescription(),
    'backends' => $two_factor->getAllBackends(),
    'missing' => $two_factor->getMissingDeps(),
]);
