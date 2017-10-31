<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences management page
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\Message;
use PhpMyAdmin\SecondFactor;
use PhpMyAdmin\Template;

/**
 * Gets some core libraries and displays a top message if required
 */
require_once 'libraries/common.inc.php';

require 'libraries/user_preferences.inc.php';

$second_factor = new SecondFactor($GLOBALS['cfg']['Server']['user']);

if (isset($_POST['2fa_remove'])) {
    if (! $second_factor->check(true)) {
        echo Template::get('prefs_second_confirm')->render([
            'form' => $second_factor->render(),
        ]);
        exit;
    } else {
        $second_factor->configure('');
        Message::rawNotice(__('Two-factor authentication has been removed.'))->display();
    }
} elseif (isset($_POST['2fa_configure'])) {
    if (! $second_factor->configure($_POST['2fa_configure'])) {
        echo Template::get('prefs_second_configure')->render([
            'form' => $second_factor->setup(),
            'configure' => $_POST['2fa_configure'],
        ]);
        exit;
    } else {
        Message::rawNotice(__('Two-factor authentication has been configured.'))->display();
    }
}

$all = array_merge([''], $second_factor->available);
$backends = [];
foreach ($all as $name) {
    $cls = $second_factor->getBackendClass($name);
    $backends[] = [
        'id' => $cls::$id,
        'name' => $cls::getName(),
        'description' => $cls::getDescription(),
    ];
}


$backend = $second_factor->backend;
echo Template::get('prefs_second')->render([
    'enabled' => $second_factor->writable,
    'num_backends' => count($second_factor->available),
    'backend_id' => $backend::$id,
    'backend_name' => $backend::getName(),
    'backend_description' => $backend::getDescription(),
    'backends' => $backends,
]);
